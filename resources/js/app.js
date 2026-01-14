import './bootstrap';
import { createApp } from 'vue';
import router from './router';
import App from './App.vue';
import VXETable from 'vxe-table';
import 'vxe-table/lib/style.css';

import ToastPlugin from './plugins/toast';

const app = createApp(App);

app.use(router);
app.use(VXETable);
app.use(ToastPlugin);

app.mount('#app');
