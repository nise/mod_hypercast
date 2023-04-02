<template>
    <div class="row h-100 justify-content-center mt-4">
        <div class="col-12 col-md-10 col-lg-7 col-xl-6">
            <Alert
                :show="getAlertState"
                :type="getAlertType"
                :message="getAlertMessage"
                @on-close="closeAlert"
            />
            <router-view />
        </div>
    </div>
</template>
<script>
import Alert from '@/components/alert.vue';
import { mapGetters, mapMutations } from 'vuex';
export default {
    components: {
        Alert,
    },
    computed: {
        ...mapGetters('notifications', [
            'getAlertType',
            'getAlertState',
            'getAlertMessage'
        ])
    },
    methods: {
        ...mapMutations('notifications', [
            'closeAlert'
        ]),
    }
};
</script>
