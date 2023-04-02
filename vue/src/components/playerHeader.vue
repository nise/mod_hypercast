<template>
    <nav
        class="hypercast-audioplayer-header align-items-center justify-content-between row"
    >
        <div class="col-1">
            <router-link to="/"
                ><font-awesome-icon icon="fa-solid fa-left-long"
            /></router-link>
        </div>
        <div class="col-10 text-center">
            <h4>{{getChapterName}}</h4>
        </div>
        <div class="col-1 d-flex justify-content-end">
            <router-link :to="`/settings/${getGroupContext.id}`"
                ><font-awesome-icon icon="fa-solid fa-gear"
            /></router-link>
        </div>
    </nav>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { mapGetters } from 'vuex';

export default defineComponent({
    data() {
        return {};
    },
    computed: {
        ...mapGetters('groups', ['getGroupContext']),
        ...mapGetters(['getChapterName']),
    },
});
</script>
