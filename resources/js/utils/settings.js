const defaultSettings = {
    company_name: '',
    fiscal_year_start: '',
    fiscal_year_end: '',
    currency: 'USD',
    default_account_type: 'asset',
    auto_generate_reference: false,
};

const getAllSettings = () => {
    if (typeof window === 'undefined') {
        return { ...defaultSettings };
    }
    return { ...defaultSettings, ...(window.__APP_SETTINGS__ || {}) };
};

export const getSetting = (key, fallback) => {
    const settings = getAllSettings();
    const value = settings[key];
    if (value === undefined || value === null || value === '') {
        return fallback !== undefined ? fallback : settings[key];
    }
    return value;
};

export const setSetting = (key, value) => {
    if (typeof window === 'undefined') {
        return;
    }
    if (!window.__APP_SETTINGS__) {
        window.__APP_SETTINGS__ = {};
    }
    window.__APP_SETTINGS__[key] = value;
};

export const getCurrency = () => getSetting('currency', 'USD');

export const getCurrencySymbol = () => {
    const currency = getCurrency();
    try {
        const parts = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).formatToParts(0);
        const symbol = parts.find((part) => part.type === 'currency');
        return symbol ? symbol.value : currency;
    } catch (error) {
        return currency;
    }
};

export const formatCurrency = (amount) => {
    const currency = getCurrency();
    const value = amount === null || amount === undefined ? 0 : amount;
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
};

export const getCompanyName = () => getSetting('company_name', '');
export const getFiscalYearStart = () => getSetting('fiscal_year_start', '');
export const getFiscalYearEnd = () => getSetting('fiscal_year_end', '');
