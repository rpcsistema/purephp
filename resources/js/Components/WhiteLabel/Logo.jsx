import React from 'react';
import { useTheme } from './ThemeProvider';

const Logo = ({ 
    type = 'main', // 'main', 'sidebar', 'login'
    className = '',
    width,
    height,
    alt,
    ...props 
}) => {
    const { logoUrl, sidebarLogoUrl, companyName, appName } = useTheme();
    
    const getLogoUrl = () => {
        switch (type) {
            case 'sidebar':
                return sidebarLogoUrl || logoUrl;
            case 'login':
            case 'main':
            default:
                return logoUrl;
        }
    };
    
    const getAltText = () => {
        if (alt) return alt;
        return `${companyName || appName} Logo`;
    };
    
    const logoSrc = getLogoUrl();
    
    // If no logo is set, show text fallback
    if (!logoSrc || logoSrc === '/images/logo.png') {
        return (
            <div className={`flex items-center ${className}`} {...props}>
                <span className="font-bold text-xl text-primary-600">
                    {companyName || appName}
                </span>
            </div>
        );
    }
    
    return (
        <img
            src={logoSrc}
            alt={getAltText()}
            className={className}
            width={width}
            height={height}
            {...props}
        />
    );
};

export default Logo;