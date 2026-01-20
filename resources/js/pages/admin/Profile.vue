<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Profile</h1>
            <p class="mt-1 text-sm text-gray-500">Manage your account details and password</p>
        </div>

        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-sm text-gray-500">Loading profile...</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-6">
            <!-- Profile Details -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Profile Details</h3>
                    <p class="mt-1 text-sm text-gray-500">Update your name and contact information</p>
                </div>
                <div class="px-4 py-5 sm:p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input
                            type="text"
                            v-model="profileForm.name"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        />
                        <p v-if="profileErrors.name" class="mt-1 text-sm text-red-600">{{ profileErrors.name[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input
                            type="email"
                            v-model="profileForm.email"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        />
                        <p v-if="profileErrors.email" class="mt-1 text-sm text-red-600">{{ profileErrors.email[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input
                            type="text"
                            v-model="profileForm.phone"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        />
                        <p v-if="profileErrors.phone" class="mt-1 text-sm text-red-600">{{ profileErrors.phone[0] }}</p>
                    </div>

                    <div class="flex justify-end">
                        <button
                            @click="saveProfile"
                            :disabled="savingProfile"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                        >
                            {{ savingProfile ? 'Saving...' : 'Save Profile' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Change Password</h3>
                    <p class="mt-1 text-sm text-gray-500">Update your account password</p>
                </div>
                <div class="px-4 py-5 sm:p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                        <input
                            type="password"
                            v-model="passwordForm.current_password"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        />
                        <p v-if="passwordErrors.current_password" class="mt-1 text-sm text-red-600">{{ passwordErrors.current_password[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input
                            type="password"
                            v-model="passwordForm.password"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        />
                        <p v-if="passwordErrors.password" class="mt-1 text-sm text-red-600">{{ passwordErrors.password[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                        <input
                            type="password"
                            v-model="passwordForm.password_confirmation"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        />
                    </div>

                    <div class="flex justify-end">
                        <button
                            @click="changePassword"
                            :disabled="savingPassword"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                        >
                            {{ savingPassword ? 'Saving...' : 'Update Password' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

export default {
    name: 'Profile',
    setup() {
        const toast = useToast();
        const loading = ref(true);
        const savingProfile = ref(false);
        const savingPassword = ref(false);
        const profileErrors = reactive({});
        const passwordErrors = reactive({});

        const profileForm = reactive({
            name: '',
            email: '',
            phone: '',
        });

        const passwordForm = reactive({
            current_password: '',
            password: '',
            password_confirmation: '',
        });

        const loadProfile = async () => {
            loading.value = true;
            try {
                const response = await axios.get('/api/profile');
                profileForm.name = response.data.name || '';
                profileForm.email = response.data.email || '';
                profileForm.phone = response.data.phone || '';
            } catch (error) {
                console.error('Error loading profile:', error);
                toast.error('Failed to load profile.');
            } finally {
                loading.value = false;
            }
        };

        const saveProfile = async () => {
            savingProfile.value = true;
            Object.keys(profileErrors).forEach(key => delete profileErrors[key]);
            try {
                const response = await axios.put('/api/profile', profileForm);
                toast.success(response.data.message || 'Profile updated successfully.');
            } catch (error) {
                if (error.response?.status === 422 && error.response?.data?.errors) {
                    Object.assign(profileErrors, error.response.data.errors);
                } else {
                    toast.error(error.response?.data?.message || 'Failed to update profile.');
                }
            } finally {
                savingProfile.value = false;
            }
        };

        const changePassword = async () => {
            savingPassword.value = true;
            Object.keys(passwordErrors).forEach(key => delete passwordErrors[key]);
            try {
                const response = await axios.put('/api/profile/password', passwordForm);
                toast.success(response.data.message || 'Password updated successfully.');
                passwordForm.current_password = '';
                passwordForm.password = '';
                passwordForm.password_confirmation = '';
            } catch (error) {
                if (error.response?.status === 422 && error.response?.data?.errors) {
                    Object.assign(passwordErrors, error.response.data.errors);
                } else {
                    toast.error(error.response?.data?.message || 'Failed to update password.');
                }
            } finally {
                savingPassword.value = false;
            }
        };

        onMounted(() => {
            loadProfile();
        });

        return {
            loading,
            savingProfile,
            savingPassword,
            profileForm,
            passwordForm,
            profileErrors,
            passwordErrors,
            saveProfile,
            changePassword,
        };
    },
};
</script>
<template>
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">My Profile</h1>
            <p class="mt-1 text-sm text-gray-500">Manage your account settings and preferences</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Profile Information -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Profile Information</h3>
                    <p class="mt-1 text-sm text-gray-500">Update your account's profile information and email address.</p>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <form @submit.prevent="updateProfile">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <input
                                    type="text"
                                    v-model="profile.name"
                                    required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input
                                    type="email"
                                    v-model="profile.email"
                                    required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone</label>
                                <input
                                    type="tel"
                                    v-model="profile.phone"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                />
                            </div>
                        </div>

                        <div class="mt-5 text-right">
                            <button
                                type="submit"
                                :disabled="updatingProfile"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                            >
                                {{ updatingProfile ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Update Password -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Update Password</h3>
                    <p class="mt-1 text-sm text-gray-500">Ensure your account is using a long, random password to stay secure.</p>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <form @submit.prevent="updatePassword">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Current Password</label>
                                <input
                                    type="password"
                                    v-model="password.current_password"
                                    required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">New Password</label>
                                <input
                                    type="password"
                                    v-model="password.password"
                                    required
                                    minlength="8"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                <input
                                    type="password"
                                    v-model="password.password_confirmation"
                                    required
                                    minlength="8"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                />
                            </div>
                        </div>

                        <div class="mt-5 text-right">
                            <button
                                type="submit"
                                :disabled="updatingPassword"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                            >
                                {{ updatingPassword ? 'Saving...' : 'Update Password' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

export default {
    name: 'Profile',
    setup() {
        const toast = useToast();
        const updatingProfile = ref(false);
        const updatingPassword = ref(false);

        const profile = reactive({
            name: '',
            email: '',
            phone: '',
        });

        const password = reactive({
            current_password: '',
            password: '',
            password_confirmation: '',
        });

        const loadProfile = async () => {
            try {
                const response = await axios.get('/api/profile');
                const user = response.data;
                profile.name = user.name;
                profile.email = user.email;
                profile.phone = user.phone || '';
            } catch (error) {
                console.error('Error loading profile:', error);
                toast.error('Failed to load profile information');
            }
        };

        const updateProfile = async () => {
            updatingProfile.value = true;
            try {
                await axios.put('/api/profile', profile);
                toast.success('Profile updated successfully');
            } catch (error) {
                console.error('Error updating profile:', error);
                if (error.response && error.response.data.errors) {
                    const errors = Object.values(error.response.data.errors).flat().join('\n');
                    toast.error(errors);
                } else {
                    toast.error('Failed to update profile');
                }
            } finally {
                updatingProfile.value = false;
            }
        };

        const updatePassword = async () => {
            updatingPassword.value = true;
            if (password.password !== password.password_confirmation) {
                toast.error('New password and confirmation do not match');
                updatingPassword.value = false;
                return;
            }

            try {
                await axios.put('/api/profile/password', password);
                toast.success('Password updated successfully');
                password.current_password = '';
                password.password = '';
                password.password_confirmation = '';
            } catch (error) {
                console.error('Error updating password:', error);
                if (error.response && error.response.data.errors) {
                    const errors = Object.values(error.response.data.errors).flat().join('\n');
                    toast.error(errors);
                } else {
                    toast.error(error.response?.data?.message || 'Failed to update password');
                }
            } finally {
                updatingPassword.value = false;
            }
        };

        onMounted(() => {
            loadProfile();
        });

        return {
            profile,
            password,
            updatingProfile,
            updatingPassword,
            updateProfile,
            updatePassword,
        };
    },
};
</script>
