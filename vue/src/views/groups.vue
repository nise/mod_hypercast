<script lang="ts">
import { defineComponent } from 'vue';
import GroupForm from '@/components/groupForm.vue';
import GroupList from '@/components/groupList.vue';
import GroupDelete from '@/components/groupDelete.vue';
import { mapGetters, mapActions } from 'vuex';
import OnboardingOverlay from '@/components/onboardingOverlay.vue';
import { storageGet } from '@/utils';
import Communication from '@/scripts/communication';

export default defineComponent({
    components: {
        GroupForm,
        GroupList,
        GroupDelete,
        OnboardingOverlay,
    },
    data() {
        return {
            isModerator: false,
        };
    },
    computed: {
        ...mapGetters(['getCourseModuleID', 'getChapterName']),
        ...mapGetters('groups', ['getGroupContext', 'getGroupList']),
    },
    methods: {
        ...mapActions('groups', [
            'updateGroupListFromDB',
            'leaveGroup',
            'showOnboarding',
        ]),
    },
    async beforeMount() {
        const tourCompleted = storageGet('user/tourCompleted');
        if (!tourCompleted) {
            this.showOnboarding();
        }
        await this.updateGroupListFromDB();

        Communication.webservice('isModerator', {
            cmid: this.getCourseModuleID,
        })
            .then((response) => {
                this.isModerator = response.success;
            })
            .catch(() => {
                this.isModerator = false;
            });
    },
});
</script>

<template>
    <div
        class="hypercast-audioplayer-header align-items-center justify-content-between row mb-3"
    >
        <div class="col-1"></div>
        <div class="col-9 text-center">
            <h4>{{getChapterName}}</h4>
        </div>
        <div class="col-2 d-flex justify-content-end align-self-start">
            <RouterLink
                v-if="isModerator"
                :to="`/statistics`"
                class="btn px-1"
                title="Nutzungsdaten einsehen"
            >
                <font-awesome-icon class="h4" icon="fa-solid fa-chart-simple" />
            </RouterLink>
            <button type="button" class="btn pr-0" @click="showOnboarding">
                <font-awesome-icon
                    icon="fa-regular fa-circle-question"
                    class="h4"
                />
            </button>
        </div>
    </div>
    <div
        class="hypercast-audioplayer-header align-items-start justify-content-between row"
    >
        <div class="col-12">
            <h1>Gruppen</h1>
        </div>
    </div>
    <OnboardingOverlay />

    <GroupList />
    <div class="modal fade" id="group-form-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gruppe anlegen</h5>
                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close"
                    >
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <GroupForm />
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add-group-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gruppe bearbeiten</h5>
                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close"
                    >
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <GroupForm :group="getGroupContext" />
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="group-delete-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gruppe löschen</h5>
                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close"
                    >
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <GroupDelete :group="getGroupContext" />
                </div>
            </div>
        </div>
    </div>

    <!-- LeaveGroup-Modal -->
    <div
        class="modal fade"
        id="leave-group-modal"
        tabindex="-1"
        aria-labelledby="deleteModalLabel"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">
                        Gruppe verlassen
                    </h5>
                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close"
                    >
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Möchtest du die Gruppe {{ getGroupContext.name }} wirklich
                    verlassen?
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-primary"
                        data-dismiss="modal"
                        @click="leaveGroup(getGroupContext)"
                    >
                        Verlassen
                    </button>
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal"
                    >
                        Abbrechen
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
