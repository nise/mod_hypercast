<script lang="ts">
import { defineComponent, PropType } from 'vue';
import { format, formatDistance, Interval, intervalToDuration } from 'date-fns';
import { de } from 'date-fns/locale';
import { Group } from '@/models';
import Communication from '@/scripts/communication';
import { mapGetters } from 'vuex';

import {
    retrieveFileDuration,
    statProgressListened,
    retrieveAllComments,
    statComments,
    buildTable,
    statVoicechat,
    getNotUser,
} from '@/utils';
import { LogEntry } from '@/models/logEntry';

export default defineComponent({
    data() {
        return {
            tableSummary: null as [string, number | null][] | null,
            log: [] as LogEntry[],
        };
    },
    props: {
        group: {
            type: Object as PropType<Group>,
            required: false,
        },
    },
    mounted: async function () {
        const response = await Communication.webservice('getStatistics', {
            cmid: this.getCourseModuleID,
        });
        if (response.success) {
            this.log = response.data as LogEntry[];
        }
        this.populateCanvasAndTable();
    },
    watch: {
        group() {
            this.populateCanvasAndTable();
        },
    },
    computed: {
        ...mapGetters(['getCourseModuleID']),
    },
    methods: {
        async populateCanvasAndTable() {
            let canvasUsage = document.getElementById(
                'Usage'
            ) as HTMLCanvasElement;
            let canvasComments = document.getElementById(
                'Comments'
            ) as HTMLCanvasElement;
            let canvasQuestion = document.getElementById(
                'Questions'
            ) as HTMLCanvasElement;
            let canvasVoicechat = document.getElementById(
                'Voicechat'
            ) as HTMLCanvasElement;
            if (
                canvasUsage &&
                canvasComments &&
                canvasQuestion &&
                canvasVoicechat
            ) {
                // Populate the canvases
                // Each canvas has a specific kind of impression limited to a specific boundary
                // opaqueness = impression / boundary
                await statProgressListened(
                    canvasUsage,
                    this.group != null ? this.group : null
                );
                statVoicechat(
                    canvasVoicechat,
                    this.log,
                    this.group != null ? this.group : null
                );
                const comments = await retrieveAllComments(
                    this.group != null ? this.group : null
                );
                statComments(canvasComments, comments, null);
                statComments(canvasQuestion, comments, 'Frage');
                // Build the table
                this.tableSummary = buildTable(comments, this.log);
                // Evaluate only if no specific group is set
                if (!this.group) {
                    let noUser = await getNotUser();
                    this.tableSummary.push([
                        'Eingeschriebene, die nie gehört haben: ' +
                            (await getNotUser()),
                        null,
                    ]);
                }
            }
        },
    },
});
</script>

<template>
    <div>
        <div class="align-items-right justify-content-space-between">
            Nutzung Audiodatei: <canvas class="col" id="Usage"></canvas><br />
        </div>
        <div class="align-items-right justify-content-space-between">
            Verteilung Voicechatnutzung:
            <canvas class="col" id="Voicechat"></canvas>
        </div>
        <div class="align-items-right justify-content-space-between">
            Verteilung Kommentare: <canvas class="col" id="Comments"></canvas
            ><br />
        </div>
        <div class="align-items-right justify-content-space-between">
            Verteilung Fragen: <canvas class="col" id="Questions"></canvas>
        </div>
        <div class="col" v-for="entry in tableSummary">
            {{ entry[0] }}: {{ entry[1] }}
        </div>
    </div>
</template>

<style scoped>
h5 {
    font-weight: 500;
    margin-top: 1rem;
    margin-bottom: 1rem;
}
.badge {
    font-size: 16px;
    margin: 4px;
    padding: 0.6em;
    border-radius: 50vh;
    position: relative;
}
.badge:hover {
    cursor: pointer;
}
li,
ul {
    overflow-wrap: anywhere;
    type: circle;
    margin-left: 20px;
}
​ .feed-container {
    padding: 8px;
}
.feed-header-container {
    display: flex;
    justify-content: space-between;
}
</style>
