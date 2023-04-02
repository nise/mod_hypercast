<script lang="ts">
import $ from 'jquery';
import { defineComponent } from 'vue';
import { mapActions, mapGetters, mapMutations } from 'vuex';
import Communication from '@/scripts/communication';
import { useToast } from 'vue-toastification';
const toast = useToast();
export default defineComponent({
    data() {
        return {
            // prevents doublet error after skipped change
            nameBeforeEdit: '',
            name: '',
            desc: '',
            size: 20,
            access: true,
            minGroupSize: 2,
            labelHead: 'Gruppe anlegen',
            // Changes has to be saved
            isDirty: false,
            // Check for doublet necessary,
            isDoublet: false,
            groupNameIsDirty: false,
            charRemainLabel: '',
        };
    },
    watch: {
        name() {
            this.isDoublet = false;
            this.charRemainLabel =
                String(this.getNameFieldLength - this.name.length) +
                ' Zeichen verbleibend';
        },
    },
    computed: {
        ...mapGetters(['getCourseModuleID']),
        ...mapGetters('groups', [
            'getGroupContext',
            'getGroupSizePreset',
            'getMaxGroupSize',
            'getNameFieldLength',
            'getEditExistingGroup',
        ]),
    },
    methods: {
        //Split add/update below validation
        async onSubmit() {
            //Checks: max. Groupsize and Doublet
            const cmid = this.getCourseModuleID;
            // prevents doublet error after skipped change
            this.groupNameIsDirty = !(this.name === this.nameBeforeEdit);
            try {
                await Communication.webservice('createGroup', {
                    cmid: cmid,
                    name: this.name,
                    description: this.desc,
                    visible: this.access,
                    maxsize: this.size,
                    onlyTry: true,
                });
            } catch (error) {
                // doublet check has to be done if the group is new or if the group name is changed
                this.isDoublet =
                    !this.getEditExistingGroup || this.groupNameIsDirty;
            }

            const isTooBig = this.size > this.getMaxGroupSize;
            const isTooSmall = this.size < 2;
            const isSmallerThanAct = this.size < this.minGroupSize;
            const isEmpty = this.name.trim().length === 0

            if (isEmpty) {
                toast.error('Das Feld Gruppenname darf nicht leer sein');
                return;
            }

            if (isTooBig || this.isDoublet || isTooSmall || isSmallerThanAct) {
                toast.error('Die Gruppeneinstellungen sind nicht gültig');
                return;
            }

            //Group is valid: Save or Update
            if (this.getEditExistingGroup) {
                if (this.isDirty || this.groupNameIsDirty) {
                    this.updateRecord();
                }
            } else {
                this.saveRecord();
            }
        },

        async updateRecord() {
            const toast = useToast();
            try {
                const response = await Communication.webservice('updateGroup', {
                    id: this.getGroupContext.id,
                    name: this.name.replace(/\s\s+/g, ' ').trim(),
                    description: this.desc,
                    visible: this.access,
                    maxsize: this.size,
                });

                if (response.name) {
                    toast.success(
                        `Die Gruppe "${response.name}" wurde geändert.`
                    );
                    this.clearAndClose();
                    await this.updateGroupListFromDB();
                }
            } catch (error) {
                toast.error(
                    'Die Gruppe konnte nicht geändert werden. Bitte versuche es erneut.'
                );
            }
        },

        async saveRecord() {
            const toast = useToast();
            try {
                const cmid = this.getCourseModuleID;
                const response = await Communication.webservice('createGroup', {
                    cmid: cmid,
                    name: this.name.replace(/\s\s+/g, ' ').trim(),
                    description: this.desc,
                    visible: this.access,
                    maxsize: this.size,
                    onlyTry: false,
                });
                if (response.name) {
                    toast.success(
                        `Die Gruppe "${response.name}" wurde erstellt.`
                    );
                    this.clearAndClose();
                    await this.updateGroupListFromDB();
                }
            } catch (error) {
                toast.error('Die Gruppe konnte nicht erstellt werden.');
            }
        },
        clearAndClose() {
            this.name = '';
            this.desc = '';
            this.labelHead = 'Gruppe anlegen';
            this.size = this.getMaxGroupSize;
            this.access = true;
            const modalName = this.getEditExistingGroup
                ? '#add-group-modal'
                : '#group-form-modal';
            this.isDoublet = false;
            this.isDirty = false;
            this.groupNameIsDirty = false;
            this.charRemainLabel = '';
            this.getModalContainer(modalName).modal('hide');
            this.setEditExistingGroup(false);
        },
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        getModalContainer(modalName: string): any {
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            return $(modalName) as any;
        },

        addShowTrigger(modalName: string): void {
            this.getModalContainer(modalName).on('show.bs.modal', () =>
                this.setupDialog()
            );
            this.getModalContainer(modalName).on('shown.bs.modal', () => {
                $('#group-form_name_input').trigger('focus');
            });
        },
        clearOnModalHide(modalName: string): void {
            this.getModalContainer(modalName).on('hidden.bs.modal', () =>
                this.clearAndClose()
            );
        },

        async populateDialog() {
            const toast = useToast();
            try {
                const response = await Communication.webservice(
                    'getGroupDetails',
                    { id: this.getGroupContext.id }
                );
                // Response with cusomErrMessage (Use-Case max. Groupsize is not mandatory)
                if (response.courseid) {
                    this.name = response.name;
                    this.desc = response.description;
                    this.size = response.maxsize;
                    this.access = response.visible;
                    this.minGroupSize = response.members.length;
                }
            } catch (error) {
                toast.error('Die Gruppe konnte nicht gelesen werden.');
            }
            this.nameBeforeEdit = this.name;
        },

        setupDialog() {
            this.size = this.getGroupSizePreset;
            this.access = true;

            //Mark Call as create or edit
            if (this.getEditExistingGroup) {
                this.labelHead = 'Gruppe bearbeiten';
                this.populateDialog();
            } else {
                this.labelHead = 'Gruppe anlegen';
            }
            this.groupNameIsDirty = false;
            this.charRemainLabel = '';
            this.clearOnModalHide('#add-group-modal');
            this.clearOnModalHide('#group-form-modal');
        },
        ...mapMutations('groups', ['setEditExistingGroup']),
        ...mapActions('groups', ['updateGroupListFromDB']),
    },
    mounted: function (): void {
        this.addShowTrigger('#add-group-modal');
        this.addShowTrigger('#group-form-modal');
        this.setupDialog();
    },
});
</script>

<template>
    <div>
        <form id="group-form" @submit.prevent="onSubmit">
            <div class="form-group">
                <label id="group-form_name_label" for="group-form_name_input">
                    Gruppenname <span class="mandatory">*</span>
                </label>
                <input
                    type="text"
                    id="group-form_name_input"
                    class="form-control"
                    :class="{ 'is-invalid': isDoublet }"
                    v-model="name"
                    :maxLength="getNameFieldLength"
                    @keypress="groupNameIsDirty = true"
                    @change="groupNameIsDirty = true"
                    required
                />
                <div
                    v-if="groupNameIsDirty && !isDoublet"
                    class="mt-2"
                    style="font-size: 80%"
                >
                    {{ charRemainLabel }}
                </div>
                <div class="invalid-feedback">
                    Gruppenname im Kurs bereits vorhanden.
                </div>
            </div>

            <div class="form-group">
                <label id="group-form_desc_label" for="group-form_desc_input">
                    Beschreibung
                </label>
                <textarea
                    cols="30"
                    rows="5"
                    type="textarea"
                    id="group-form_size_input"
                    class="form-control"
                    v-model="desc"
                    @change="isDirty = true"
                ></textarea>
            </div>

            <div class="form-group">
                <label id="group-form_size_label" for="group-form_size_input">
                    Maximale Gruppengröße <span class="mandatory">*</span>
                </label>

                <div class="row align-items-center">
                    <input
                        type="range"
                        min="2"
                        max="20"
                        id="group-form_size_inputAlt"
                        class="form-range col-10"
                        :class="{ 'is-invalid': size < minGroupSize }"
                        v-model="size"
                        @change="isDirty = true"
                    />
                    <span class="col-2"> {{ size }}</span>
                    <span class="col-12 invalid-feedback"
                        >Neue maximale Gruppengröße ist kleiner als die aktuelle
                        Größe der Gruppe.</span
                    >
                </div>
            </div>

            <div class="form-group form-check">
                <input
                    v-model="access"
                    class="form-check-input"
                    type="checkbox"
                    id="group-form_visible_check"
                    @change="isDirty = true"
                />
                <label class="form-check-label" for="group-form_visible_check">
                    <span>
                        Die Gruppe ist in der kursweiten Gruppenliste sichtbar
                        und jedes Kursmitglied kann ihr beitreten.</span
                    >
                </label>
            </div>

            <button
                v-if="getEditExistingGroup"
                type="submit"
                id="group-form_submit-button-save"
                class="btn btn-primary"
            >
                Speichern
            </button>
            <button
                v-else
                type="submit"
                id="group-form_submit-button"
                class="btn btn-primary"
            >
                Anlegen
            </button>
        </form>
        <div class="form-group ml-2">
            <label id="group-form_footer_label">
                <span class="mandatory">*</span> Pflichtfeld
            </label>
        </div>
    </div>
</template>

<style scoped>
h1,
form {
    padding: 12px;
}
div {
    padding-bottom: 12px;
}
.mandatory {
    color: #ca3120;
}
</style>
