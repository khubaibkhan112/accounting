import { ref } from 'vue';

const dialogState = ref({
    show: false,
    title: '',
    message: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    type: 'warning', // 'warning', 'danger', 'info'
    onConfirm: null,
    onCancel: null,
});

export function useConfirmDialog() {
    const showDialog = (options) => {
        return new Promise((resolve, reject) => {
            dialogState.value = {
                show: true,
                title: options.title || 'Confirm Action',
                message: options.message || 'Are you sure you want to proceed?',
                confirmText: options.confirmText || 'Confirm',
                cancelText: options.cancelText || 'Cancel',
                type: options.type || 'warning',
                onConfirm: () => {
                    dialogState.value.show = false;
                    resolve(true);
                },
                onCancel: () => {
                    dialogState.value.show = false;
                    resolve(false);
                },
            };
        });
    };

    const confirmDelete = (itemName = 'this item') => {
        return showDialog({
            title: 'Confirm Deletion',
            message: `Are you sure you want to delete ${itemName}? This action cannot be undone.`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            type: 'danger',
        });
    };

    const confirmAction = (message, title = 'Confirm Action') => {
        return showDialog({
            title,
            message,
            type: 'warning',
        });
    };

    return {
        dialogState,
        showDialog,
        confirmDelete,
        confirmAction,
    };
}
