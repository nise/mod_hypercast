<script lang="ts">
import $ from 'jquery';
import { defineComponent, PropType } from 'vue';
import { Group } from '@/models';
import { mapActions } from 'vuex';
import { useToast } from 'vue-toastification';
const toast = useToast();
export default defineComponent({
    props: {
        group: {
            type: Object as PropType<Group>,
            required: true,
        },
    },
    data() {
        return {
            enteredGroupName: '',
        };
    },
    methods: {
        ...mapActions('groups', ['deleteGroup']),
        async onSubmit() {
            if (!this.hasEnteredSameGroupName) {
                toast.error('Der eingegebene Gruppenname ist falsch');
                return;
            }

            this.deleteGroup(this.group);
            this.clearModalOnHide();
            this.getModalContainer().modal('hide');
        },
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        getModalContainer() {
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            return $('#group-delete-modal') as any;
        },
        clearModalOnHide(): void {
            this.getModalContainer().on(
                'hidden.bs.modal',
                () => (this.enteredGroupName = '')
            );
        },
    },
    computed: {
        hasEnteredSameGroupName(): boolean {
            return this.group.name === this.enteredGroupName;
        },
    },
});
</script>

<template>
    <div>
        <form id="group-delete" @submit.prevent="onSubmit">
            <p class="user-select-none">
                Möchtest du die Gruppe <b>{{ group.name }}</b> wirklich
                unwiderruflich löschen? Gib zur Sicherheit den Gruppennamen ein!
            </p>
            <div class="form-group">
                <input
                    type="text"
                    id="group-delete_name_input"
                    class="form-control"
                    v-model="enteredGroupName"
                    required
                />
            </div>
            <div class="float-right">
                <button
                    class="btn btn-secondary modal-default-button"
                    data-dismiss="modal"
                >
                    Abbrechen
                </button>
                <button
                    type="submit"
                    id="group-delete_submit-button"
                    class="btn btn-danger ml-2"
                    :disabled="!hasEnteredSameGroupName"
                >
                    Löschen
                </button>
            </div>
        </form>
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
</style>
