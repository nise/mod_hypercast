<?php

require '../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class PlaySession implements MessageComponentInterface {
    const GROUP_ID_KEY = 'groupId';
    const ACTIVE_SESSION_KEY = 'activeSession';
    const USER_ID_KEY = 'userId';

    protected SplObjectStorage $clients;
    protected array $clientUserMapping;
    protected array $groupProgressMapping;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->clientUserMapping = array();
        $this->groupProgressMapping = array();
    }

    public function onOpen(ConnectionInterface $conn) {
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $queryArray);
        $userid = intval($queryArray['userid']);
        $groupId = $queryArray['groupid'] == 'undefined' ? -1 : intval($queryArray['groupid']);
        echo "User with id $userid in group $groupId is connected to server\n";

        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        $resourceId = $conn->resourceId;
        $this->clientUserMapping[$resourceId] = [
            self::USER_ID_KEY => $userid,
            self::GROUP_ID_KEY => $groupId,
            self::ACTIVE_SESSION_KEY => false
        ];

        if ($groupId != -1) {
            $group_members = $this->getActiveGroupMembers($groupId);
            $this->sendGroupInfo($conn, $groupId, $group_members);
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $resourceId = $from->resourceId;

        $groupId = $this->getGroupId($resourceId);
        if ($groupId == -1) {
            echo "No group specified therefore skip sending message\n";
            return;
        }
        $data = json_decode($msg);

        if ($data->{'key'} == 'updateTimestamp') {
            $groupId = $data->{'payload'}->{'groupid'};
            $timestamp = $data->{'payload'}->{'timestamp'};
            $this->groupProgressMapping[$groupId] = $timestamp;
            // We want to provide that time update to other live-group members, though sometimes that time update is just for our own DB.
            // Adds functionality that live-group member marker will update immediately.
            if ($this->hasActiveSession($resourceId) && $data->{'payload'}->{'notifyGroup'}) {
                $this->sendMessageToGroupMembers($resourceId, $msg, $groupId);
            }
            return;
        }

        if ($data->{'key'} == 'getGroupInfo') {
            $groupId = $this->getGroupId($resourceId);
            if ($groupId != -1) {
                $group_members = $this->getActiveGroupMembers($groupId);
                $this->sendGroupInfo($from, $groupId, $group_members);
            }
        }

        if ($data->{'key'} == 'joinLiveSession') {
            $this->joinLiveSession($resourceId, $groupId, $data->{'payload'});
            return;
        }

        if ($data->{'key'} == 'webRTCMessage') {
            $this->transWebRTCMessage($resourceId, $groupId, $msg);
            return;
        }

        if ($data->{'key'} == 'leaveLiveSession') {
            $this->leaveLiveSession($resourceId, $groupId);
            return;
        }

        if ($this->hasActiveSession($resourceId)) {
            $this->sendMessageToGroupMembers($resourceId, $msg, $groupId);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $resourceId = $conn->resourceId;

        if ($this->hasActiveSession($resourceId)) {
            $groupId = $this->getGroupId($resourceId);

            $this->leaveLiveSession($resourceId, $groupId);
        }

        $this->clients->detach($conn);
        unset($this->clientUserMapping[$resourceId]);

        echo "Connection {$resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    /**
     * @param $senderResourceId
     * @param string $msg
     * @param mixed $groupId
     * @return void
     */
    public function sendMessageToGroupMembers($senderResourceId, string $msg, int $groupId): void {
        echo sprintf('Connection %d sending message "%s" to connections from group with id %s' . "\n", $senderResourceId, $msg, $groupId);

        $count = 0;
        foreach ($this->clients as $client) {
            if ($senderResourceId === $client->resourceId) {
                // The sender is not the receiver
                continue;
            }

            if ($this->getGroupId($client->resourceId) != $groupId) {
                // The sender and the receiver are not in the same group
                continue;
            }

            if (!$this->hasActiveSession($client->resourceId)) {
                // The receiver is not joined the live session jet
                continue;
            }

            $client->send($msg);
            $count++;
        }
        echo "Message send to $count connections\n";
    }

    /**
     * @param $resourceId
     * @param int $groupId
     * @return void
     */
    public function joinLiveSession($resourceId, int $groupId, int $timestamp): void {
        $userId = $this->getUserId($resourceId);

        if (empty($this->getActiveGroupMembers($groupId))) {
            $this->groupProgressMapping[$groupId] = $timestamp;
        }

        $this->clientUserMapping[$resourceId][self::ACTIVE_SESSION_KEY] = true;
        $message = json_encode(
            array(
                'key' => 'userConnected',
                'payload' => $userId
            )
        );
        $this->sendMessageToGroupMembers($resourceId, $message, $groupId);
        echo "User with id $userId joined live session in group $groupId\n";

        $group_members = $this->getActiveGroupMembers($groupId);
        foreach ($this->clients as $client) {
            $client_data = $this->clientUserMapping[$client->resourceId];
            if ($client_data[self::GROUP_ID_KEY] === $groupId) {
                $this->sendGroupInfo($client, $groupId, $group_members);
            }
        }
    }

    /**
     * @param $resourceId
     * @param int $groupId
     * @return void
     */
    public function transWebRTCMessage($resourceId, int $groupId, String $passedMessage): void {
        $userId = $this->getUserId($resourceId);

        $this->clientUserMapping[$resourceId][self::ACTIVE_SESSION_KEY] = true;
        $message = json_encode(
            array(
                'key' => 'getWebRTCMessage',
                'payload' => $passedMessage
            )
        );
        $this->sendMessageToGroupMembers($resourceId, $message, $groupId);
    }

    /**
     * @param $resourceId
     * @param int $groupId
     * @return void
     */
    public function leaveLiveSession($resourceId, int $groupId): void {
        $userId = $this->getUserId($resourceId);
        $this->clientUserMapping[$resourceId][self::ACTIVE_SESSION_KEY] = false;
        $message = json_encode(
            array(
                'key' => 'userDisconnected',
                'payload' => $userId
            )
        );
        $this->sendMessageToGroupMembers($resourceId, $message, $groupId);
        echo "User with id $userId leaved live session in group $groupId\n";

        $group_members = $this->getActiveGroupMembers($groupId);

        if (empty($group_members)) {
            $this->groupProgressMapping[$groupId] = 0;
        }

        foreach ($this->clients as $client) {
            $client_data = $this->clientUserMapping[$client->resourceId];
            if ($client_data[self::GROUP_ID_KEY] === $groupId) {
                $this->sendGroupInfo($client, $groupId, $group_members);
            }
        }
    }

    /**
     * @param $resourceId
     * @return int
     */
    public function getUserId($resourceId): int {
        return $this->clientUserMapping[$resourceId][self::USER_ID_KEY];
    }

    /**
     * @param $resourceId
     * @return int
     */
    public function getGroupId($resourceId): int {
        return $this->clientUserMapping[$resourceId][self::GROUP_ID_KEY];
    }

    /**
     * @param $resourceId
     * @return bool
     */
    public function hasActiveSession($resourceId): bool {
        return $this->clientUserMapping[$resourceId][self::ACTIVE_SESSION_KEY];
    }

    /**
     * @param int $groupId
     * @return array
     */
    public function getActiveGroupMembers(int $groupId): array {
        $group_members = array();
        // send group members and current time to user
        foreach ($this->clients as $client) {
            $client_data = $this->clientUserMapping[$client->resourceId];
            if ($client_data[self::GROUP_ID_KEY] === $groupId && $client_data[self::ACTIVE_SESSION_KEY]) {
                $group_members[] = $client_data[self::USER_ID_KEY];
            }
        }
        return $group_members;
    }

    /**
     * @param ConnectionInterface $conn
     * @param int $groupId
     * @param array $group_members
     * @return void
     */
    public function sendGroupInfo(ConnectionInterface $conn, int $groupId, array $group_members): void {
        $conn->send(json_encode(
            array(
                'key' => 'groupInfo',
                'payload' => array(
                    'members' => $group_members,
                    'timestamp' => $this->groupProgressMapping[$groupId] ?? 0,
                    'activeSession' => !empty($group_members)
                )
            )
        ));
    }
}