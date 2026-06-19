// Security utility functions
const SecurityUtil = {
    /**
     * Escape HTML special characters to prevent XSS
     */
    escapeHTML(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    /**
     * Escape HTML in object properties
     */
    sanitizeObject(obj) {
        if (!obj) return obj;
        const sanitized = {};
        for (const key in obj) {
            if (typeof obj[key] === 'string') {
                sanitized[key] = this.escapeHTML(obj[key]);
            } else {
                sanitized[key] = obj[key];
            }
        }
        return sanitized;
    },

    /**
     * Safely insert HTML content
     */
    setTextContent(element, text) {
        if (element) {
            element.textContent = text;
        }
    },

    /**
     * Create safe DOM element with text
     */
    createSafeElement(tag, text, className = '') {
        const el = document.createElement(tag);
        el.textContent = text;
        if (className) el.className = className;
        return el;
    }
};

// Patch common DOM methods for safety
const originalInnerHTML = Element.prototype.innerHTML;
Object.defineProperty(Element.prototype, 'innerHTML', {
    get() {
        return originalInnerHTML.call(this);
    },
    set(value) {
        // Log potential XSS attempts in development
        if (typeof value === 'string' && (value.includes('<script') || value.includes('javascript:'))) {
            console.warn('⚠️ Potential XSS attempt detected:', value);
            return;
        }
        originalInnerHTML.call(this, value);
    }
});
