<script lang="ts">
import { defineComponent } from 'vue';
import { mapActions, mapGetters, mapMutations } from 'vuex';
import { PrivacySettings } from '@/models';
import { playSound } from '@/utils';
import { useToast } from 'vue-toastification';

const toast = useToast();

export default defineComponent({
    data() {
        return {
            displayUser: true,
            displayOthers: true,
            audioCues: {
                figureref: true,
                tableref: true,
                image: true,
                url: true,
                type: 'subtle',
            },
            textSize: 1,
        };
    },
    watch: {
        displayUser(newValue) {
            this.displayOthers = newValue;
        },
        // figureref and tablerefs are both internal references for the user, so same settings
        'audioCues.figureref'(newValue) {
            this.audioCues.tableref = newValue;
        },
    },
    computed: {
        ...mapGetters('groups', ['getGroupContext']),
        ...mapGetters('settings', [
            'getPrivacySettings',
            'getSoundfiles',
            'getFlashySoundfiles',
            'getTextSize',
        ]),
    },
    methods: {
        ...mapActions('settings', [
            'loadPrivacySettings',
            'savePrivacySettings',
        ]),
        ...mapMutations('settings', ['setTextSize']),
        async populateFields() {
            const settings: PrivacySettings = await this.loadPrivacySettings();
            this.displayUser = !settings.hideUser;
            this.displayOthers = !settings.hideOthers;
            this.audioCues = JSON.parse(settings.audioCues);
            this.textSize = this.getTextSize;
        },
        async onSubmit() {
            const settings: PrivacySettings = {
                groupid: this.getGroupContext.id,
                hideUser: !this.displayUser,
                hideOthers: !this.displayOthers,
                audioCues: JSON.stringify(this.audioCues),
            };
            await this.savePrivacySettings(settings);
            this.setTextSize(this.textSize);
            toast.success('Die Einstellungen wurden gespeichert');
            this.$router.push(`/player/${this.getGroupContext.id}`);
        },

        playSample(cueType) {
            if (this.audioCues.type === 'flashy') {
                playSound(this.getFlashySoundfiles[cueType]);
            } else {
                playSound(this.getSoundfiles[cueType]);
            }
        },
    },
    async beforeMount() {
        await this.populateFields();
    },
});
</script>

<template>
    <nav
        class="hypercast-audioplayer-header align-items-center justify-content-between row"
    >
        <div class="col-1">
            <router-link :to="`/player/${getGroupContext.id}`">
                <font-awesome-icon icon="fa-solid fa-left-long" />
            </router-link>
        </div>
        <div class="col-10 text-center">
            <h4>Einstellungen</h4>
        </div>
        <div class="col-1 d-flex justify-content-end"></div>
    </nav>

    <div>
        <form id="settings-form" @submit.prevent="onSubmit">
            <h5>Privatsphäre-Einstellungen für den Audioplayer</h5>
            <div class="form-group form-check">
                <input
                    v-model="displayUser"
                    class="form-check-input"
                    type="checkbox"
                    id="settings-displayUser"
                />
                <label class="form-check-label" for="settings-displayUser">
                    <span>
                        Meinen Hörfortschritt für andere Gruppenmitglieder
                        anzeigen.</span
                    >
                </label>
            </div>
            <div class="form-group form-check form-check-sub">
                <input
                    v-model="displayOthers"
                    class="form-check-input"
                    type="checkbox"
                    id="settings-displayOthers"
                    :disabled="!displayUser"
                />
                <label class="form-check-label" for="settings-displayOthers">
                    <span>
                        Den Hörfortschritt anderer Gruppenmitglieder
                        anzeigen.</span
                    >
                </label>
            </div>
            <h5>
                Akustische Hinweise bei Zusatzinhalten zum Audiodokument
                abspielen
            </h5>
            <div class="form-check form-check-inline">
                <input
                    class="form-check-input"
                    type="radio"
                    name="flashyCues"
                    id="inlineRadio1"
                    value="subtle"
                    v-model="audioCues.type"
                />
                <label class="form-check-label" for="inlineRadio1">
                    <span class="ml-1"> Dezent</span></label
                >
            </div>
            <div class="form-check form-check-inline">
                <input
                    class="form-check-input"
                    type="radio"
                    name="flashyCues"
                    id="inlineRadio2"
                    value="flashy"
                    v-model="audioCues.type"
                />
                <label class="form-check-label" for="inlineRadio2">
                    <span class="ml-1"> Auffällig</span></label
                >
            </div>

            <div class="form-group form-check">
                <input
                    v-model="audioCues.image"
                    class="form-check-input"
                    type="checkbox"
                    id="audiocues-image"
                />
                <label class="form-check-label" for="audiocues-image">
                    <span> Abbildung</span>
                </label>
                <span class="ml-2 audiocue-sample-play-button"
                    ><font-awesome-icon
                        icon="fa-regular fa-circle-play"
                        @click="playSample('image')"
                /></span>
            </div>
            <div class="form-group form-check">
                <input
                    v-model="audioCues.figureref"
                    class="form-check-input"
                    type="checkbox"
                    id="audiocues-figureref"
                />
                <label class="form-check-label" for="audiocues-figureref">
                    <span> Verweis auf eine andere Stelle im Dokument</span>
                </label>
                <span class="ml-2 audiocue-sample-play-button"
                    ><font-awesome-icon
                        icon="fa-regular fa-circle-play"
                        @click="playSample('figureref')"
                /></span>
            </div>
            <div class="form-group form-check">
                <input
                    v-model="audioCues.url"
                    class="form-check-input"
                    type="checkbox"
                    id="audiocues-url"
                />
                <label class="form-check-label" for="audiocues-url">
                    <span> Verweis auf externen Inhalt (URL)</span>
                </label>
                <span class="ml-2 audiocue-sample-play-button"
                    ><font-awesome-icon
                        icon="fa-regular fa-circle-play"
                        @click="playSample('url')"
                /></span>
            </div>
            <h5>Schriftgröße des Audiotranskripts</h5>
            <div class="row align-items-center ml-2" style="height: 3rem">
                <input
                    type="range"
                    min="0.75"
                    max="1.75"
                    step="0.25"
                    id="course-text-font-size"
                    class="form-range col-6"
                    v-model.number="textSize"
                />
                <span class="col-6" :style="{ 'font-size': `${textSize}rem` }">
                    Beispiel</span
                >
            </div>
            <button
                type="submit"
                id="settings-form-button-save"
                class="btn btn-primary mt-2"
            >
                Speichern
            </button>
        </form>
    </div>
</template>

<style scoped>
.audiocue-sample-play-button {
    cursor: pointer;
}

.form-check {
    margin-left: 1rem;
    margin-bottom: 0.5rem;
}
.form-check-sub {
    margin-left: 2.5rem;
}
.form-check-inline {
    margin-bottom: 0.5rem;
}
h5 {
    font-weight: 500;
    margin-top: 1rem;
    margin-bottom: 1rem;
}
</style>
