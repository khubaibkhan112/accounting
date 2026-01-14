<template>
    <div id="app">
        <router-view />
        <NotificationContainer />
        <ConfirmDialog />
    </div>
</template>

<script>
import { defineComponent } from 'vue';
import NotificationContainer from './components/NotificationContainer.vue';
import ConfirmDialog from './components/ConfirmDialog.vue';

export default defineComponent({
    name: 'App',
    components: {
        NotificationContainer,
        ConfirmDialog,
    }
});
</script>

<style>
#app {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}
</style>

