<script lang="ts">
import { defineComponent } from 'vue';
import { Group } from '@/models';
import GroupListItem from './groupListItem.vue';
import { mapGetters } from 'vuex';
import { isUserMemberOfGroup } from '@/utils';

export default defineComponent({
    components: {
        GroupListItem,
    },
    data() {
        return {
            filter: '',
        };
    },
    computed: {
        filteredGroups(): Group[] {
            if (!this.filter) return this.visibleGroups;
            return this.visibleGroups.filter((group) => {
                const filterQuery = this.filter.toLocaleLowerCase();
                // const tokenizedFilterQuery = filterQuery.split(' ')

                const filteredByGroupname = group.name
                    .toLocaleLowerCase()
                    .includes(filterQuery);

                let filteredByMembername = group.members.some((user) =>
                    `${user.firstname} ${user.lastname}`
                        .toLocaleLowerCase()
                        .includes(filterQuery)
                );
                return filteredByGroupname || filteredByMembername;
            });
        },

        visibleGroups(): Group[] {
            return this.getGroupList.filter(
                (group: Group) =>
                    group.visible === true ||
                    isUserMemberOfGroup(this.getUserID, group)
            );
        },
        ...mapGetters(['getUserID']),
        ...mapGetters('groups', ['getGroupList']),
    },
});
</script>

<template>
    <div class="row justify-content-md-between form-group">
        <div class="col-12 col-md-auto text-center mb-3 mb-md-0">
            <button
                type="button"
                class="btn btn-primary"
                data-toggle="modal"
                data-target="#group-form-modal"
            >
                Gruppe anlegen
            </button>
        </div>
        <div class="col-12 col-md-6">
            <input
                type="search"
                class="form-control"
                placeholder="Nach Namen filtern..."
                v-model="filter"
            />
        </div>
    </div>
    <div class="row flex-column">
        <div class="col-12">
            <ul class="ml-0 pl-0">
                <li v-for="group of filteredGroups" :key="group.id" class="row">
                    <div class="col-12">
                        <div class="border px-3">
                            <GroupListItem :group="group" />
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>
