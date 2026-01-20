import './bootstrap';
import { createApp } from 'vue';
import router from './router';
import App from './App.vue';
import ToastPlugin from './plugins/toast';

const app = createApp(App);

app.use(router);
app.use(ToastPlugin);

app.mount('#app');
