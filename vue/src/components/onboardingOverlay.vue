<script lang="ts">
import { defineComponent } from 'vue';
import Overlay from '@/components/utils/overlay.vue';
import { mapActions, mapGetters } from 'vuex';
import { storageSet } from '@/utils';

export default defineComponent({
    components: {
        Overlay,
    },
    data() {
        return {
            steps: [
                {
                    id: 1,
                    icon: 'fa-solid fa-user-group',
                    class: 'feature-icon',
                    content: `<strong>Gründe eine Gruppe</strong>, um gemeinsam mit anderen vertonte Kurstexte zu hören`,
                },
                {
                    id: 2,
                    icon: 'fa-solid fa-user fa-xs',
                    class: 'personal-marker',
                    content: `<strong>Steuere den Audioplayer</strong> mit deinem persönlichen Fortschrittsmarker`,
                },
                {
                    id: 3,
                    icon: 'fa-solid fa-icons',
                    class: 'feature-icon',
                    content: `<strong>Höre und betrachte Zusatzinhalte</strong> zum Audiodokument`,
                },
                {
                    id: 4,
                    icon: 'fa-solid fa-users fa-xs',
                    class: 'group-marker',
                    content: `<strong>Verbinde dich mit einer Live-Session</strong>, um über gemeinsam Gehörtes zu sprechen`,
                },
                {
                    id: 5,
                    icon: 'fa-solid fa-comments',
                    class: 'feature-icon',
                    content: `<strong>Erstelle Kommentare und Diskussionen</strong>, um das Gelernte zu vertiefen`,
                },
            ],
        };
    },
    computed: {
        ...mapGetters(['getUserID', 'getPluginBaseURL']),
        ...mapGetters('groups', ['getGroupList', 'getShowOnboarding']),
    },
    methods: {
        ...mapActions('groups', ['hideOnboarding']),
        setTourCompleted() {
            storageSet('user/tourCompleted', true);
            this.hideOnboarding();
        },
    },
});
</script>

<template>
    <Overlay
        :show="getShowOnboarding"
        @close="hideOnboarding"
        :show-header="false"
    >
        <div class="container mt-5">
            <div class="row justify-content-center mb-md-5">
                <div class="col-8 text-center">
                    <img :src="`${getPluginBaseURL}/pix/icon.svg`" width="40" />
                    <h1>Willkommen bei HyperCast!</h1>
                </div>
            </div>
            <div class="row mx-2 h-100 justify-content-center">
                <div class="col">
                    <div
                        class="row p-1 p-md-3 align-items-center"
                        v-for="step of steps"
                        :key="step.id"
                    >
                        <div class="col-3 col-sm-2">
                            <div :class="step.class">
                                <font-awesome-icon :icon="step.icon" />
                            </div>
                        </div>
                        <div class="col-9 col-sm-10">
                            <div v-html="step.content"></div>
                        </div>
                    </div>
                    <div class="row my-3 justify-content-center">
                        <div class="col-auto">
                            <button
                                type="button"
                                class="btn btn-primary"
                                @click="setTourCompleted"
                            >
                                weiter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Overlay>
</template>

<style scoped>
.feature-icon {
    font-size: 35px;
}
h1 {
    font-weight: 700;
}
.personal-marker {
    display: flex;
    flex-direction: column;
    justify-content: center;
    width: 40px;
    height: 38px;
    font-size: 12pt;
    color: white;
    background-color: var(--progress-bar-user-progress-color);
    border-radius: 50px 50px 5px 50px;
}
.group-marker {
    display: flex;
    flex-direction: column;
    justify-content: center;
    width: 40px;
    height: 38px;
    font-size: 12pt;
    color: white;
    background-color: var(--progress-bar-group-progress-color);
    border-radius: 50px 50px 5px 50px;
}
</style>
