import React, { createContext, useContext, useEffect } from 'react';
import { usePage } from '@inertiajs/react';

const ThemeContext = createContext();

export const useTheme = () => {
    const context = useContext(ThemeContext);
    if (!context) {
        throw new Error('useTheme must be used within a ThemeProvider');
    }
    return context;
};

export const ThemeProvider = ({ children }) => {
    const { props } = usePage();
    const whiteLabelSettings = props.whiteLabelSettings || {};

    useEffect(() => {
        // Apply CSS variables to document root
        if (whiteLabelSettings.colors) {
            const root = document.documentElement;
            Object.entries(whiteLabelSettings.colors).forEach(([key, value]) => {
                root.style.setProperty(`--color-${key}`, value);
            });
        }

        // Apply custom CSS
        if (whiteLabelSettings.custom_css) {
            let styleElement = document.getElementById('white-label-dynamic-css');
            if (!styleElement) {
                styleElement = document.createElement('style');
                styleElement.id = 'white-label-dynamic-css';
                document.head.appendChild(styleElement);
            }
            styleElement.textContent = whiteLabelSettings.custom_css;
        }

        // Apply custom JS
        if (whiteLabelSettings.custom_js) {
            let scriptElement = document.getElementById('white-label-dynamic-js');
            if (!scriptElement) {
                scriptElement = document.createElement('script');
                scriptElement.id = 'white-label-dynamic-js';
                document.head.appendChild(scriptElement);
            }
            scriptElement.textContent = whiteLabelSettings.custom_js;
        }

        // Update favicon
        if (whiteLabelSettings.favicon_url) {
            let favicon = document.querySelector('link[rel="icon"]');
            if (!favicon) {
                favicon = document.createElement('link');
                favicon.rel = 'icon';
                document.head.appendChild(favicon);
            }
            favicon.href = whiteLabelSettings.favicon_url;
        }

        // Update page title
        if (whiteLabelSettings.meta_title) {
            document.title = whiteLabelSettings.meta_title;
        }

        // Update meta description
        if (whiteLabelSettings.meta_description) {
            let metaDescription = document.querySelector('meta[name="description"]');
            if (!metaDescription) {
                metaDescription = document.createElement('meta');
                metaDescription.name = 'description';
                document.head.appendChild(metaDescription);
            }
            metaDescription.content = whiteLabelSettings.meta_description;
        }

        // Update meta keywords
        if (whiteLabelSettings.meta_keywords) {
            let metaKeywords = document.querySelector('meta[name="keywords"]');
            if (!metaKeywords) {
                metaKeywords = document.createElement('meta');
                metaKeywords.name = 'keywords';
                document.head.appendChild(metaKeywords);
            }
            metaKeywords.content = whiteLabelSettings.meta_keywords;
        }
    }, [whiteLabelSettings]);

    const themeValue = {
        settings: whiteLabelSettings,
        colors: whiteLabelSettings.colors || {},
        companyName: whiteLabelSettings.company_name || 'SaaS App',
        appName: whiteLabelSettings.app_name || 'SaaS App',
        tagline: whiteLabelSettings.tagline || '',
        logoUrl: whiteLabelSettings.logo_url || '/images/logo.png',
        sidebarLogoUrl: whiteLabelSettings.sidebar_logo_url || '/images/logo-sidebar.png',
        faviconUrl: whiteLabelSettings.favicon_url || '/favicon.ico',
        footerText: whiteLabelSettings.footer_text || '',
        dashboardWelcomeMessage: whiteLabelSettings.dashboard_welcome_message || 'Welcome to your dashboard!',
        enabledModules: whiteLabelSettings.enabled_modules || [],
        socialLinks: whiteLabelSettings.social_links || {},
        contactInfo: whiteLabelSettings.contact_info || {},
        themeConfig: whiteLabelSettings.theme_config || {},
        
        // Helper functions
        isModuleEnabled: (module) => {
            return whiteLabelSettings.enabled_modules?.includes(module) ?? true;
        },
        
        getColor: (colorName, fallback = '#000000') => {
            return whiteLabelSettings.colors?.[colorName] || fallback;
        },
        
        getSocialLink: (platform) => {
            return whiteLabelSettings.social_links?.[platform] || '';
        },
        
        getContactInfo: (field) => {
            return whiteLabelSettings.contact_info?.[field] || '';
        },
        
        getThemeConfig: (key, fallback = null) => {
            return whiteLabelSettings.theme_config?.[key] || fallback;
        }
    };

    return (
        <ThemeContext.Provider value={themeValue}>
            {children}
        </ThemeContext.Provider>
    );
};

export default ThemeProvider;