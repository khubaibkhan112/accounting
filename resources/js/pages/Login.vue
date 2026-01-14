<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Sign in to your account
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Accounting Software
                </p>
            </div>
            <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input
                            id="email"
                            v-model="form.email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            required
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="Email address"
                            :class="{ 'border-red-500': errors.email }"
                        />
                        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input
                            id="password"
                            v-model="form.password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="Password"
                            :class="{ 'border-red-500': errors.password }"
                        />
                        <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input
                            id="remember-me"
                            v-model="form.remember"
                            name="remember-me"
                            type="checkbox"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="loading"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="loading">Signing in...</span>
                        <span v-else>Sign in</span>
                    </button>
                </div>

                <div v-if="error" class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">{{ error }}</h3>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

export default {
    name: 'Login',
    setup() {
        const router = useRouter();
        const loading = ref(false);
        const error = ref('');
        const errors = reactive({
            email: '',
            password: '',
        });

        const form = reactive({
            email: '',
            password: '',
            remember: false,
        });

        const handleLogin = async () => {
            loading.value = true;
            error.value = '';
            errors.email = '';
            errors.password = '';

            try {
                const response = await axios.post('/api/auth/login', {
                    email: form.email,
                    password: form.password,
                    remember: form.remember,
                });

                if (response.data.user) {
                    // Redirect to admin dashboard
                    router.push('/admin');
                }
            } catch (err) {
                console.error('Login error:', err);
                
                if (err.response?.status === 422) {
                    const validationErrors = err.response.data.errors;
                    if (validationErrors?.email) {
                        errors.email = Array.isArray(validationErrors.email) 
                            ? validationErrors.email[0] 
                            : validationErrors.email;
                    }
                    if (validationErrors?.password) {
                        errors.password = Array.isArray(validationErrors.password) 
                            ? validationErrors.password[0] 
                            : validationErrors.password;
                    }
                    error.value = 'Please check your credentials and try again.';
                } else if (err.response?.data?.message) {
                    error.value = err.response.data.message;
                } else {
                    error.value = 'An error occurred during login. Please try again.';
                }
            } finally {
                loading.value = false;
            }
        };

        return {
            form,
            loading,
            error,
            errors,
            handleLogin,
        };
    },
};
</script>

