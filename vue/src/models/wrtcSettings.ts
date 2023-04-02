export interface WRTCSettings {
    UUID: string;
    localUUID: string;
    destUUID: string;
    peerUUID: string;
    displayName: string;
    ice: RTCIceCandidate;
    sdp: RTCSessionDescription;
}
