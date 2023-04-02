<script lang="ts">
import { defineComponent } from 'vue';
import { mapGetters, mapActions } from 'vuex';
import Communication from '@/scripts/communication';
import { LogEntry } from '@/models/logEntry';
import { Group } from '@/models/group';
import FeedListItem from '@/components/feedListItem.vue';
import Multiselect from 'vue-multiselect';
import StatisticPanel from '@/components/statisticPanel.vue';

export default defineComponent({
    components: {
        FeedListItem,
        StatisticPanel,
        Multiselect,
    },

    data() {
        return {
            logEntries: [] as LogEntry[],
            filteredLogEntries: [] as LogEntry[],
            isEmpty: false,
            groupList: [] as Group[],
            selectedGroup: null as Group | null,
            showLog: false,
        };
    },
    watch: {
        selectedGroup() {
            this.filteredLogEntries = Object.assign([], this.logEntries);
            this.filteredLogEntries = this.filteredLogEntries.filter(
                (element) =>
                    element.event != 'ls_paused' && element.event != 'vc_unmute'
            );
            if (this.selectedGroup === null) return;
            this.filteredLogEntries = this.filteredLogEntries.filter(
                (logEntry) => {
                    return logEntry.groupid === this.selectedGroup!.id;
                }
            );
        },
    },
    methods: {
        onClose(selectedOption, id) {
            //since vue-multiselect has no deselect-event
            if (selectedOption === this.selectedGroup)
                this.selectedGroup = null;
        },
        ...mapActions('groups', ['updateGroupListFromDB']),
    },
    computed: {
        ...mapGetters(['getCourseModuleID']),
        ...mapGetters('groups', ['getGroupContext', 'getGroupList']),
    },
    async beforeMount() {
        //GroupList gets lost on reload
        await this.updateGroupListFromDB();

        const response = await Communication.webservice('getStatistics', {
            cmid: this.getCourseModuleID,
        });
        if (response.success) {
            this.logEntries = response.data as LogEntry[];
        }
        this.filteredLogEntries = Object.assign([], this.logEntries);
        this.filteredLogEntries = this.filteredLogEntries.filter(
            (element) =>
                element.event != 'ls_paused' && element.event != 'vc_unmute'
        );
        this.groupList = this.getGroupList;
    },
});
</script>
<template>
    <nav
        class="hypercast-audioplayer-header align-items-center justify-content-between row"
    >
        <div class="col-1">
            <router-link :to="`/`">
                <font-awesome-icon icon="fa-solid fa-left-long" />
            </router-link>
        </div>
        <div class="col-10 text-center">
            <h4>Statistiken</h4>
        </div>

        <div class="col-1 d-flex justify-content-end"></div>
    </nav>
    <div>
        <div class="row align-items-end justify-content-between mb-2">
            <div class="col-3"><h4>Aktivitäten</h4></div>
            <div class="col-8">
                <multiselect
                    class="button"
                    v-model="selectedGroup"
                    deselect-label="Auswahl aufheben"
                    select-label="Auswählen"
                    selected-label="Ausgewählt"
                    id="name"
                    track-by="name"
                    label="name"
                    placeholder="Nach Gruppe filtern..."
                    :options="groupList"
                    :searchable="false"
                    :allow-empty="false"
                    @close="onClose"
                >
                </multiselect>
            </div>
        </div>
    </div>

    <StatisticPanel :group="selectedGroup" style="margin-top: 4px" />
    <h5 @click="showLog = !showLog" class="activity-details">Detailansicht Aktivitäten</h5>
    <li
        v-if="showLog"
        v-for="log of filteredLogEntries"
        :key="log.id"
        class="row"
        style="margin-left: 0"
    >
        <div class="col-12">
            <div class="border px-3">
                <FeedListItem :log-entry="log" />
            </div>
        </div>
    </li>

    <div v-if="isEmpty" class="feed-containe border px-3">
        <div class="feed-header-container">
            <b>Nutzungsübersicht</b>
        </div>
        <span
            >Folgende Aktionen werden angezeigt, sobald entsprechende
            Aktivitäten verzeichnet wurden:
            <li>Gründen, Bearbeiten und Löschen von Gruppen</li>
            <li>Beitreten und Verlassen von Gruppen</li>
            <li>Verfassen, Bearbeiten und Löschen von Kommentaren</li>
        </span>
    </div>
</template>

<style
    src="../../node_modules/vue-multiselect/dist/vue-multiselect.ssr.css"
></style>

<style scoped>
h5 {
    font-weight: 500;
    margin-top: 1rem;
    margin-bottom: 1rem;
}
li,
ul {
    overflow-wrap: anywhere;
    type: circle;
    margin-left: 20px;
}
.feed-header-container {
    display: flex;
    justify-content: space-between;
}
.activity-details:hover {
    text-decoration: underline;
    cursor: pointer;
}
</style>
