<script lang="ts">
import { defineComponent, PropType } from 'vue';
import { Group, User } from '@/models';
import { mapActions, mapGetters, mapMutations } from 'vuex';
import { useToast } from 'vue-toastification';
import { generateInviteLink, statProgressListened } from '@/utils';

export default defineComponent({
    props: {
        group: {
            type: Object as PropType<Group>,
            required: true,
        },
    },
    data(): { maxDisplayMembers: number } {
        return {
            maxDisplayMembers: 5,
        };
    },
    computed: {
        ...mapGetters(['getUserID']),
    },
    mounted: function (): void {
        this.resolveGroupProgress();
    },
    methods: {
        getFullname(member: User): string {
            return `${member.firstname} ${member.lastname}`;
        },

        isMember(group: Group): boolean {
            const userid = this.getUserID;
            return group.members.some((member) => member.id === userid);
        },

        isOwner(group: Group): boolean {
            const userid = this.getUserID;
            return group.usercreated.id === userid;
        },

        isFull(group: Group): boolean {
            return group.members.length >= group.maxsize;
        },

        getMembersToDisplay(group: Group): User[] {
            return group.members.slice(0, this.maxDisplayMembers);
        },

        truncate(value: string, size: number): string {
            return value.length > size ? value.slice(0, size - 1) + '…' : value;
        },
        async resolveGroupProgress() {
            let canvas = document.getElementById(
                'groupProgress_' + this.group.id
            ) as HTMLCanvasElement;
            if (canvas) await statProgressListened(canvas, this.group);
        },
        ...mapMutations('notifications', ['showAlert']),
        ...mapMutations('groups', ['setGroupContext']),
        ...mapActions('groups', ['editGroup', 'joinGroup']),
        showInviteLink(group: Group) {
            this.setGroupContext(group);
            navigator.clipboard.writeText(generateInviteLink(group));
            useToast().info(
                'Einladungslink wurde in die Zwischenablage kopiert.'
            );
        },
    },
});
</script>

<template>
    <div class="row justify-content-between">
        <div class="col-12 col-md py-2">
            <div class="row">
                <div class="col-12">
                    <div class="row">
                        <div class="col-auto d-flex align-items-baseline">
                            <font-awesome-icon
                                v-if="!group.visible"
                                icon="fa-solid fa-user-lock"
                                class="text-secondary mr-2"
                            />
                            <router-link
                                v-if="isMember(group)"
                                :to="`/player/${group.id}`"
                                class="h2 user-input"
                                :class="
                                    group.visible
                                        ? 'text-dark'
                                        : 'text-secondary'
                                "
                                >{{ group.name }}
                            </router-link>
                            <h2 v-else class="user-input">
                                {{ group.name }}
                            </h2>
                            <div
                                v-if="isMember(group)"
                                class="dropdown show text-muted ml-2"
                            >
                                <a
                                    role="button"
                                    id="dropdownMenuLink"
                                    data-toggle="dropdown"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                >
                                    <i
                                        class="fa fa-ellipsis-v fa-fw"
                                        aria-hidden="true"
                                    ></i>
                                </a>

                                <div
                                    class="dropdown-menu"
                                    aria-labelledby="dropdownMenuLink"
                                >
                                    <a
                                        v-if="isMember(group)"
                                        class="dropdown-item"
                                        @click="showInviteLink(group)"
                                        :class="{ disabled: isFull(group) }"
                                        >Einladungslink teilen</a
                                    >
                                    <a
                                        v-if="isOwner(group)"
                                        class="dropdown-item"
                                        data-toggle="modal"
                                        data-target="#add-group-modal"
                                        @click="editGroup(group)"
                                        >Gruppe bearbeiten</a
                                    >
                                    <a
                                        v-if="isOwner(group)"
                                        class="dropdown-item"
                                        data-toggle="modal"
                                        data-target="#group-delete-modal"
                                        @click="setGroupContext(group)"
                                        >Gruppe löschen</a
                                    >
                                    <a
                                        v-if="
                                            isMember(group) && !isOwner(group)
                                        "
                                        @click="setGroupContext(group)"
                                        class="dropdown-item"
                                        data-toggle="modal"
                                        data-target="#leave-group-modal"
                                        >Gruppe verlassen</a
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md">
                    <p class="user-input">
                        {{ truncate(group.description, 300) }}
                    </p>
                </div>
            </div>
            <div
                class="members row justify-content-center justify-content-md-start"
            >
                <div class="col-auto">
                    <img
                        v-for="member of getMembersToDisplay(group)"
                        :key="member.id"
                        :src="member.profileimageurl"
                        width="35"
                        height="35"
                        :alt="getFullname(member)"
                        :title="getFullname(member)"
                    />
                    <span
                        v-if="group.members.length > maxDisplayMembers"
                        class="more-members"
                        >+{{ group.members.length - maxDisplayMembers }}</span
                    >
                    <span class="ml-2"
                        >{{ group.members.length }} / {{ group.maxsize }}</span
                    >
                </div>
            </div>
        </div>
        <div
            class="col-12 col-md-auto mt-3 mt-md-0 pb-3 pb-md-0 d-flex align-items-center justify-content-center"
        >
            <RouterLink
                v-if="isMember(group)"
                :to="`/player/${group.id}`"
                class="btn btn-outline-primary"
                >zum Gruppenplayer</RouterLink
            >
            <button
                v-else-if="!isFull(group)"
                type="button"
                class="btn btn-primary d-flex align-items-center"
                @click="joinGroup(group)"
                style="gap: 6px"
            >
                <span>Beitreten</span>
                <i class="icon m-0 fa fa-play-circle"></i>
            </button>
        </div>
    </div>
    <span class="align-items-right justify-content-space-between"
        >Hörfortschritt:
        <canvas :id="'groupProgress_' + group.id"></canvas>
    </span>
</template>

<style scoped>
.user-input {
    overflow-wrap: anywhere;
}
a:hover {
    text-decoration: none;
}
.members {
    line-height: 35px;
}
.members img,
.members .more-members {
    outline: 2px solid white;
    border-radius: 50%;
}

.members img:not(:first-of-type),
.members .more-members {
    margin-left: -10px;
}

.more-members {
    display: inline-block;
    width: 35px;
    height: 35px;
    background-color: #e6e6e6;
    text-align: center;
}
</style>
