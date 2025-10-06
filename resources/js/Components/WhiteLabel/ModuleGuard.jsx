import React from 'react';
import { useTheme } from './ThemeProvider';

const ModuleGuard = ({ 
    module, 
    children, 
    fallback = null,
    showFallback = false 
}) => {
    const { isModuleEnabled } = useTheme();
    
    // If module is enabled, render children
    if (isModuleEnabled(module)) {
        return children;
    }
    
    // If module is disabled and we should show fallback
    if (showFallback && fallback) {
        return fallback;
    }
    
    // Otherwise render nothing
    return null;
};

export default ModuleGuard;