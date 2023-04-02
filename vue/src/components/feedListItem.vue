<script lang="ts">
import {defineComponent, PropType} from 'vue';
import {LogEntry} from "@/models/logEntry";
import {format, formatDistance, Interval, intervalToDuration} from "date-fns";
import {de} from "date-fns/locale";

export default defineComponent({
    props: {
        logEntry: {
            type: Object as PropType<LogEntry>,
            required: true,
        },
    },
    methods: {
        convertUnixTime(value: number): string {
            return format(new Date(value * 1000), 'dd.MM.yyy HH:mm');
        },
        toReadableTime(value: number): string {
            const now = new Date();
            const interval: Interval = {start: value * 1000, end: now};
            if (intervalToDuration(interval).days) {
                return `am ${this.convertUnixTime(value)}`;
            } else {
                const formatted = formatDistance(interval.start, interval.end, {
                    locale: de,
                });
                return `vor ${formatted}`;
            }
        },
        getGroupName(logEntry: LogEntry) {
            if(logEntry.groupname){
                return logEntry.groupname;
            }
            let data = JSON.parse(logEntry.data);
            return `${data.groupname ?? ''} (gelöscht)`;
        },
        toDisplayText(logEntry: LogEntry): string {
            switch(logEntry.event) {
                case 'create_group':
                    return `Erstellte die Gruppe: ${this.getGroupName(logEntry)}`;
                case 'update_group':
                    return `Bearbeitete die Gruppe ${this.getGroupName(logEntry)}`;
                case 'delete_group':
                    return `Löschte die Gruppe ${this.getGroupName(logEntry)}`;
                case 'join_group':
                    return `Ist der Gruppe ${this.getGroupName(logEntry)} beigetreten`;
                case 'leave_group':
                    return `Hat die Gruppe ${this.getGroupName(logEntry)} verlassen`;
                case 'create_comment':
                    return `Hat einen Kommentar in der Gruppe ${this.getGroupName(logEntry)} verfasst`;
                case 'update_comment':
                    return `Hat einen Kommentar in der Gruppe ${this.getGroupName(logEntry)} bearbeitet`;
                case 'delete_comment':
                    return `Hat einen Kommentar in der Gruppe ${this.getGroupName(logEntry)} gelöscht`;
            }
            return '';
        }
    }
});
</script>

<template>
    <div class="feed-container">
        <div class="feed-header-container">
            <b>{{ logEntry.firstname }} {{ logEntry.lastname }}</b>
            <i>{{ toReadableTime(logEntry.timecreated) }}</i>
        </div>
        <span>{{ toDisplayText(logEntry) }}</span>
    </div>
</template>

<style scoped>

.feed-container {
    padding: 8px;
}

.feed-header-container {
    display: flex;
    justify-content: space-between;
}
</style>
