<script lang="ts">
import { defineComponent } from 'vue';
import { mapActions, mapGetters, mapMutations } from 'vuex';
import Hammer from 'hammerjs';
import { MembersPlaytime, User } from '@/models';
import {
    checkCollision,
    clamp,
    getOffset,
    percentageInputBetweenRange,
    registerClickaway,
} from '@/utils';
import Communication from '@/scripts/communication';
import AutoScrollButton from '@/components/autoScrollButton.vue';
import { createPopper, Instance } from '@popperjs/core';
import { LiveSession } from '@/models/liveSession';

export default defineComponent({
    name: 'ProgressBar',
    components: {
        AutoScrollButton,
    },
    props: {
        currentTime: {
            type: Number,
            required: true,
        },
        duration: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            groupUsers: [] as User[],
            membersPlaytime: [] as MembersPlaytime[],
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            timer: null as any,
            collisions: [] as { first: string; second: string }[],
            opacityValue: '0.5',
            popper: undefined as Instance | undefined,
        };
    },
    computed: {
        ...mapGetters('player', ['getScrolledByUser']),
        percentageFilled() {
            return clamp((this.currentTime / this.duration) * 100, 0, 100);
        },
        formattedAudioTime() {
            let time = this.isSeeking
                ? this.seekingPercentToCurrentTime
                : this.currentTime;
            let hours = Math.floor(time / 3600);
            let minutes = Math.floor((time - hours * 3600) / 60);
            let seconds = Math.floor(time - hours * 3600 - minutes * 60);

            return `${hours ? hours + ':' : ''}${
                minutes < 10 ? '0' + minutes : minutes
            }:${seconds < 10 ? '0' + seconds : seconds}`;
        },
        seekingPercentToCurrentTime() {
            return clamp(
                this.duration * (this.getPercentageSeeked / 100),
                0,
                this.duration
            );
        },
        filteredGroupUsersWithProgress() {
            // hide myself+users without progress+users in live session
            return this.groupUsers.filter(
                (user) =>
                    user.id !== this.getUserID &&
                    this.getPercentageFilledOfUser(user.id) != 0 &&
                    !(this.getLiveSession() as LiveSession).members?.includes(
                        user.id
                    )
            );
        },
        filteredGroupUsersInLiveSession() {
            return this.groupUsers.filter((user) =>
                (this.getLiveSession() as LiveSession).members?.includes(
                    user.id
                )
            );
        },
        ...mapGetters('groups', ['getGroupContext']),
        ...mapGetters('player', [
            'getPercentageSeekedToCurrentTime',
            'getPercentageSeeked',
            'isSeeking',
            'isLiveSessionRunning',
            'isLiveSessionJoined',
        ]),
        ...mapGetters('settings', ['getPrivacySettings']),
        ...mapGetters(['getCourseModuleID', 'getUserID']),
    },
    methods: {
        getPercentageFilledOfUser(userid: number) {
            const timestamp =
                this.membersPlaytime.find(
                    (memberPlaytime) => memberPlaytime.userid === userid
                )?.timestamp ?? 0;
            if (timestamp == 0) {
                return timestamp;
            }
            return clamp((timestamp / this.duration) * 100, 0, 100);
        },
        getPercentageFilledOfLiveSession() {
            const liveSession: LiveSession = this.getLiveSession();
            if (!liveSession.timestamp) return 0;

            return clamp((liveSession.timestamp / this.duration) * 100, 0, 100);
        },
        async getMembersPlaytime() {
            try {
                const cmid = this.getCourseModuleID;
                const groupid = this.getGroupContext.id;
                const memberPlaytimeReponse = await Communication.webservice(
                    'getGroupMembersPlaytime',
                    { cmid, groupid }
                );
                this.membersPlaytime = memberPlaytimeReponse.membersPlaytime;
            } catch (err) {
                this.showAlert([
                    'danger',
                    'Fortschritt der Gruppenmitglieder konnte nicht geladen werden.',
                ]);
            }
        },
        getFullName(user: User): string {
            return `${user.firstname} ${user.lastname}`;
        },
        getDisplayName(user: User): string {
            if (user.id == this.getUserID) {
                return 'Ich';
            }
            return this.getFullName(user);
        },
        getElementFromUserId(userId: string): HTMLElement {
            return document.querySelector(
                `[data-user-id='${userId}']`
            ) as HTMLElement;
        },
        getElementIndex(element: HTMLElement) {
            return +(element.getAttribute('data-index') ?? '0');
        },

        hidePopper: function () {
            if (this.popper) {
                let tooltip = this.$refs['progress-tooltip'] as HTMLDivElement;
                tooltip?.removeAttribute('data-show');
                this.popper.destroy();
                this.popper = undefined;
            }
        },
        showPopper: function (e) {
            let tooltip = this.$refs['progress-tooltip'] as HTMLDivElement;
            let element: undefined | HTMLDivElement = undefined;

            if (this.isLiveSessionRunning) {
                element = this.$refs.groupMarker as HTMLDivElement;
                if (this.isLiveSessionJoined) {
                    element = this.$refs
                        .personalMarkerContainer as HTMLDivElement;
                }
            } else {
                element = this.$refs.personalMarkerContainer as HTMLDivElement;
            }

            if (!element) return;

            this.popper = createPopper(element, tooltip, {
                placement: 'left',
                modifiers: [
                    {
                        name: 'offset',
                        options: {
                            offset: [0, 8],
                        },
                    },
                ],
            });
            tooltip.setAttribute('data-show', '');
        },
        toggleTooltip: function (e) {
            let tooltip = this.$refs['progress-tooltip'] as HTMLDivElement;

            if (tooltip.hasAttribute('data-show')) {
                this.hidePopper();
            } else {
                this.showPopper(e);
            }
        },
        userMarkerClicked(e) {
            if (!this.isLiveSessionRunning || this.isLiveSessionJoined) {
                this.toggleTooltip(e);
            }
        },
        liveSessionMarkerClicked(e) {
            this.toggleTooltip(e);
        },
        startLiveSession() {
            this.setSpeed(1);
            this.hidePopper();
            this.joinLiveSession();
        },
        endLiveSession() {
            this.hidePopper();
            this.leaveLiveSession();
        },
        ...mapGetters('player', ['getLiveSession']),
        ...mapMutations('notifications', ['showAlert']),
        ...mapMutations('player', [
            'setPercentageSeeked',
            'setIsSeeking',
            'setSpeed',
        ]),
        ...mapActions('player', [
            'updateCurrentTime',
            'joinLiveSession',
            'leaveLiveSession',
            'loadLiveSessionGroupInfo',
        ]),
        ...mapActions('settings', ['loadPrivacySettings']),
    },
    watch: {
        membersPlaytime() {
            const marker = Array.from(
                document.getElementsByClassName('marker')
            );
            this.collisions = checkCollision(
                marker.map((item) => getOffset(item))
            );
        },
        collisions() {
            // make all marker non transparent
            const marker = Array.from(
                document.getElementsByClassName(
                    'marker'
                ) as HTMLCollectionOf<HTMLElement>
            );
            marker.forEach((marker) => {
                marker.style.opacity = '1';
            });

            // make collided transparent
            this.collisions.forEach((collision) => {
                const topElement = this.getElementFromUserId(collision.first);
                const bottomElement = this.getElementFromUserId(
                    collision.second
                );

                if (collision.first === this.getUserID.toString()) {
                    bottomElement.style.opacity = this.opacityValue;
                } else if (collision.second === this.getUserID.toString()) {
                    topElement.style.opacity = this.opacityValue;
                } else if (
                    this.getElementIndex(topElement) >
                    this.getElementIndex(bottomElement)
                ) {
                    bottomElement.style.opacity = this.opacityValue;
                } else {
                    topElement.style.opacity = this.opacityValue;
                }
            });
        },
    },
    async beforeMount() {
        await this.getMembersPlaytime();
        this.groupUsers = this.getGroupContext.members;
    },
    async mounted(): Promise<void> {
        this.timer = setInterval(() => {
            this.getMembersPlaytime();
        }, 3000);

        const hammertime = new Hammer(this.$refs.personalMarkerContainer as HTMLDivElement, {});
        hammertime
            .get('pan')
            .set({ direction: Hammer.DIRECTION_VERTICAL, threshold: 1 });
        hammertime.on('panstart', () => {
            this.setIsSeeking(true);
        });

        hammertime.on('pan', (ev) => {
            const progressEl = this.$refs.progress as Element;
            const personalMarkerEl = this.$refs.personalMarker as Element;

            const { bottom, y } = progressEl.getBoundingClientRect();
            this.setPercentageSeeked(
                clamp(
                    percentageInputBetweenRange(
                        ev.center.y + personalMarkerEl.clientHeight / 2,
                        y,
                        bottom
                    ),
                    0,
                    100
                )
            );
        });

        hammertime.on('panend', () => {
            this.setIsSeeking(false);
            this.updateCurrentTime({
                notifyGroup: true,
                data: { exact: this.seekingPercentToCurrentTime },
            });
            this.setPercentageSeeked(0);
        });

        hammertime.on('tap', (e) => {
            this.userMarkerClicked(e);
        });

        await this.loadPrivacySettings();
        if (!this.getPrivacySettings.hideOthers) {
            this.timer = setInterval(() => {
                this.getMembersPlaytime();
                if (this.isLiveSessionRunning && !this.isLiveSessionJoined) {
                    this.loadLiveSessionGroupInfo();
                }
            }, 3000);
        }

        registerClickaway(
            [
                this.$refs.personalMarkerContainer as HTMLDivElement,
                this.$refs['progress-tooltip'] as HTMLDivElement,
            ],
            this.hidePopper
        );
    },
    beforeUnmount() {
        clearInterval(this.timer);
    },
});
</script>

<template>
    <div class="progress" ref="progress">
        <div
            class="group-user-marker-container marker"
            v-for="(user, index) in filteredGroupUsersWithProgress"
            v-show="!getPrivacySettings.hideOthers"
            :key="user.id"
            :data-user-id="user.id"
            :data-index="index"
            :style="{
                top: `calc(${getPercentageFilledOfUser(user.id)}% - 38px)`,
            }"
        >
            <div class="group-user-marker">
                <span>
                    <img
                        :key="user.id"
                        :src="user.profileimageurl"
                        width="32"
                        height="32"
                        :alt="getFullName(user)"
                        :title="getFullName(user)"
                    />
                </span>
            </div>
        </div>
        <div
            v-show="!isLiveSessionJoined && isLiveSessionRunning"
            :data-user-id="getGroupContext.id"
            class="live-session-marker marker"
            @click="liveSessionMarkerClicked"
            :style="{
                top: `calc(${getPercentageFilledOfLiveSession()}% - 38px)`,
            }"
        >
            <div class="group-marker position-relative" ref="groupMarker">
                <font-awesome-icon
                    icon="fa-solid fa-user-group"
                    style="color: white"
                ></font-awesome-icon>
            </div>
        </div>
        <div
            class="progress-bar position-relative"
            role="progressbar"
            :aria-valuenow="percentageFilled"
            aria-valuemin="0"
            aria-valuemax="100"
            :style="{
                height:
                    (isSeeking ? getPercentageSeeked : percentageFilled) + '%',
            }"
        >
            <div
                ref="personalMarkerContainer"
                :data-user-id="getUserID"
                class="personal-marker-container marker"
            >
                <div
                    ref="personalMarker"
                    class="position-relative"
                    :class="{
                        'personal-marker': !isLiveSessionJoined,
                        'group-marker': isLiveSessionJoined,
                    }"
                >
                    <span>
                        <font-awesome-icon
                            v-if="!isLiveSessionJoined"
                            icon="fa-solid fa-user"
                        ></font-awesome-icon>
                        <font-awesome-icon
                            v-if="isLiveSessionJoined"
                            icon="fa-solid fa-user-group"
                        ></font-awesome-icon>
                    </span>
                    <span
                        class="personal-marker-time"
                        :class="{
                            'personal-marker-time__up': isSeeking,
                            'personal-marker-time__down': !isSeeking,
                        }"
                        >{{ formattedAudioTime }}</span
                    >
                </div>
            </div>
        </div>
    </div>
    <div
        ref="progress-tooltip"
        role="tooltip"
        id="progress-tooltip"
        class="progress-tooltip"
    >
        <div
            v-if="!isLiveSessionJoined && !isLiveSessionRunning"
            class="progress-tooltip-container"
        >
            <span
                >Aktuell läuft keine Live-Session. Möchtest du eine
                starten?</span
            >
            <button
                type="button"
                class="btn-primary progress-tooltip-button"
                @click="startLiveSession"
            >
                <font-awesome-icon icon="fa-solid fa-user-group" />
                Starten
            </button>
            <hr v-if="getScrolledByUser" />
            <AutoScrollButton v-if="getScrolledByUser" />
        </div>
        <div
            v-if="
                isLiveSessionJoined ||
                (!isLiveSessionJoined && isLiveSessionRunning)
            "
            class="progress-tooltip-container"
        >
            <span>In der Live-Session</span>

            <div class="progress-tooltip-users-container">
                <div
                    v-for="(user, index) in filteredGroupUsersInLiveSession"
                    :key="user.id"
                    :data-user-id="user.id"
                    :data-index="index"
                    class="progress-tooltip-user-row"
                >
                    <img
                        :key="user.id"
                        :src="user.profileimageurl"
                        width="32"
                        height="32"
                        :alt="getFullName(user)"
                        :title="getFullName(user)"
                    />
                    <span>{{ getDisplayName(user) }}</span>
                </div>
            </div>

            <button
                v-if="isLiveSessionJoined"
                type="button"
                class="btn-primary progress-tooltip-button"
                @click="endLiveSession"
            >
                <font-awesome-icon icon="fa-solid fa-right-from-bracket" />
                Verlassen
            </button>
            <button
                v-if="!isLiveSessionJoined"
                type="button"
                class="btn-primary progress-tooltip-button"
                @click="startLiveSession"
            >
                <font-awesome-icon icon="fa-solid fa-right-to-bracket" />
                Teilnehmen
            </button>
            <hr v-if="getScrolledByUser" />
            <AutoScrollButton v-if="getScrolledByUser" />
        </div>
        <div id="arrow" data-popper-arrow></div>
    </div>
</template>

<style scoped>
.personal-marker-container,
.live-session-marker,
.group-user-marker-container {
    width: auto;
    height: auto;
    display: flex;
    position: absolute;
    right: 15px;
    flex-direction: column;
    align-items: flex-end;
}

.personal-marker {
    display: flex;
    flex-direction: column;
    justify-content: center;
    width: 40px;
    height: 38px;
    font-size: 12pt;
    background-color: var(--progress-bar-user-progress-color);
    opacity: 1;
    border-top-left-radius: 50px;
    border-top-right-radius: 50px;
    border-bottom-left-radius: 50px;
    border-bottom-right-radius: 5px;
}

.personal-marker-container,
.live-session-marker {
    pointer-events: all;
    cursor: pointer;
}

.personal-marker-container {
    bottom: 0;
}

.group-marker {
    display: flex;
    flex-direction: column;
    justify-content: center;
    width: 40px;
    height: 38px;
    font-size: 12pt;
    background-color: var(--progress-bar-group-progress-color);
    border-radius: 50px 50px 5px 50px;
}

.group-user-marker {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 40px;
    height: 38px;
    font-size: 12pt;
    background-color: rgba(0, 0, 0, 0);
    border-style: solid;
    border-width: thin;
    border-color: var(--progress-bar-user-progress-color);
    border-top-left-radius: 50px;
    border-top-right-radius: 50px;
    border-bottom-left-radius: 50px;
    border-bottom-right-radius: 5px;
}

img {
    outline: 2px solid white;
    border-radius: 50%;
    float: inside;
}

.personal-marker-time {
    position: absolute;
    right: 0;
    color: var(--progress-bar-user-progress-color);
    font-size: 14px;
    margin: 0;
}

.personal-marker-time__down {
    top: 47px;
}

.personal-marker-time__up {
    bottom: 47px;
}

.progress,
.progress-bar {
    overflow: visible;
}

.progress {
    margin-top: 8rem;
    margin-bottom: 3rem;
    position: absolute;
    right: 0.55rem;
    top: 50px;
    bottom: 50px;
    border-radius: 10px;
    width: 0.5rem;
    height: auto;
    background-color: var(--progress-bar-background-color);
}

.progress .progress-bar {
    width: 0.5rem;
    border-radius: 10px;
    background-color: var(--progress-bar-user-progress-color);
}

.progress-tooltip {
    pointer-events: all;
    display: none;
    width: 250px;
    max-width: 50%;
    background-color: var(--progress-bar-tooltip-background-color);
    border: 1px solid var(--border-color-dark);
    border-radius: 8px;
    padding: 12px;
    line-height: 100%;
}

.progress-tooltip[data-show] {
    display: block;
}

.progress-tooltip-container {
    display: flex;
    flex-direction: column;
    justify-content: space-around;
    gap: 8px;
}

.progress-tooltip-button {
    align-self: end;
}

.progress-tooltip-users-container {
    display: flex;
    flex-direction: column;
    gap: 4px;
    border: 1px solid var(--border-color-dark);
    border-radius: 8px;
    padding: 4px;
}

.progress-tooltip-user-row {
    display: flex;
    flex-direction: row;
    justify-content: flex-start;
    gap: 8px;
}

.progress-tooltip-user-row > span {
    display: inline-flex;
    align-items: center;
}

#arrow,
#arrow::before {
    position: absolute;
    width: 8px;
    height: 8px;
    background: inherit;
    border-top: inherit;
    border-right: inherit;
}

#arrow {
    visibility: hidden;
}

#arrow::before {
    visibility: visible;
    content: '';
    transform: rotate(45deg);
}

#progress-tooltip[data-popper-placement^='top'] > #arrow {
    bottom: -4px;
}

#progress-tooltip[data-popper-placement^='bottom'] > #arrow {
    top: -4px;
}

#progress-tooltip[data-popper-placement^='left'] > #arrow {
    right: -4px;
}

#progress-tooltip[data-popper-placement^='right'] > #arrow {
    left: -4px;
}

hr {
    margin: 0;
    border-style: inset;
    border-width: 1px;
}
</style>
