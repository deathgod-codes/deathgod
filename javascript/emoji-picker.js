/**
 * Emoji Picker Component
 * Provides an interactive emoji selector with categories and search
 */

class EmojiPicker {
    constructor(options = {}) {
        this.onEmojiSelect = options.onEmojiSelect || (() => {});
        this.container = options.container || document.body;
        this.recentEmojis = this.loadRecentEmojis();
        
        this.categories = {
            'recent': { name: 'Recently Used', icon: '🕒', emojis: this.recentEmojis },
            'smileys': { name: 'Smileys & People', icon: '😀', emojis: [
                '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃', '😉', '😊',
                '😇', '🥰', '😍', '🤩', '😘', '😗', '😚', '😙', '😋', '😛', '😜', '🤪',
                '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏',
                '😒', '🙄', '😬', '🤥', '😌', '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕',
                '🤢', '🤮', '🤧', '🥵', '🥶', '😵', '🤯', '🤠', '🥳', '😎', '🤓', '🧐',
                '😕', '😟', '🙁', '☹️', '😮', '😯', '😲', '😳', '🥺', '😦', '😧', '😨',
                '😰', '😥', '😢', '😭', '😱', '😖', '😣', '😞', '😓', '😩', '😫', '🥱',
                '👋', '🤚', '🖐️', '✋', '🖖', '👌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙',
                '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎', '✊', '👊', '🤛', '🤜',
                '👏', '🙌', '👐', '🤲', '🤝', '🙏', '💪', '👶', '👧', '🧒', '👦', '👨'
            ]},
            'animals': { name: 'Animals & Nature', icon: '🐶', emojis: [
                '🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁', '🐮',
                '🐷', '🐽', '🐸', '🐵', '🙈', '🙉', '🙊', '🐒', '🐔', '🐧', '🐦', '🐤',
                '🐣', '🐥', '🦆', '🦅', '🦉', '🦇', '🐺', '🐗', '🐴', '🦄', '🐝', '🐛',
                '🦋', '🐌', '🐞', '🐜', '🦟', '🦗', '🕷️', '🦂', '🐢', '🐍', '🦎', '🦖',
                '🦕', '🐙', '🦑', '🦐', '🦞', '🦀', '🐡', '🐠', '🐟', '🐬', '🐳', '🐋',
                '🦈', '🐊', '🐅', '🐆', '🦓', '🦍', '🦧', '🐘', '🦛', '🦏', '🐪', '🐫',
                '🦒', '🦘', '🐃', '🐂', '🐄', '🐎', '🐖', '🐏', '🐑', '🦙', '🐐', '🦌',
                '🌵', '🎄', '🌲', '🌳', '🌴', '🌱', '🌿', '☘️', '🍀', '🎍', '🎋', '🍃',
                '🍂', '🍁', '🍄', '🌾', '💐', '🌷', '🌹', '🥀', '🌺', '🌸', '🌼', '🌻'
            ]},
            'food': { name: 'Food & Drink', icon: '🍕', emojis: [
                '🍏', '🍎', '🍐', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🍈', '🍒', '🍑',
                '🥭', '🍍', '🥥', '🥝', '🍅', '🍆', '🥑', '🥦', '🥬', '🥒', '🌶️', '🌽',
                '🥕', '🥔', '🍠', '🥐', '🥯', '🍞', '🥖', '🥨', '🧀', '🥚', '🍳', '🧈',
                '🥞', '🧇', '🥓', '🥩', '🍗', '🍖', '🦴', '🌭', '🍔', '🍟', '🍕', '🥪',
                '🥙', '🧆', '🌮', '🌯', '🥗', '🥘', '🥫', '🍝', '🍜', '🍲', '🍛', '🍣',
                '🍱', '🥟', '🦪', '🍤', '🍙', '🍚', '🍘', '🍥', '🥠', '🥮', '🍢', '🍡',
                '🍧', '🍨', '🍦', '🥧', '🧁', '🍰', '🎂', '🍮', '🍭', '🍬', '🍫', '🍿',
                '🍩', '🍪', '🌰', '🥜', '🍯', '🥛', '🍼', '☕', '🍵', '🧃', '🥤', '🍶'
            ]},
            'activities': { name: 'Activities', icon: '⚽', emojis: [
                '⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🪀', '🏓',
                '🏸', '🏒', '🏑', '🥍', '🏏', '🥅', '⛳', '🪁', '🏹', '🎣', '🤿', '🥊',
                '🥋', '🎽', '🛹', '🛷', '⛸️', '🥌', '🎿', '⛷️', '🏂', '🪂', '🏋️', '🤼',
                '🤸', '🤺', '⛹️', '🤾', '🏌️', '🏇', '🧘', '🏊', '🤽', '🚣', '🧗', '🚴',
                '🚵', '🎪', '🎭', '🎨', '🎬', '🎤', '🎧', '🎼', '🎹', '🥁', '🎷', '🎺',
                '🎸', '🪕', '🎻', '🎲', '♟️', '🎯', '🎳', '🎮', '🎰', '🧩'
            ]},
            'travel': { name: 'Travel & Places', icon: '✈️', emojis: [
                '🚗', '🚕', '🚙', '🚌', '🚎', '🏎️', '🚓', '🚑', '🚒', '🚐', '🚚', '🚛',
                '🚜', '🦯', '🦽', '🦼', '🛴', '🚲', '🛵', '🏍️', '🛺', '🚨', '🚔', '🚍',
                '🚘', '🚖', '🚡', '🚠', '🚟', '🚃', '🚋', '🚞', '🚝', '🚄', '🚅', '🚈',
                '🚂', '🚆', '🚇', '🚊', '🚉', '✈️', '🛫', '🛬', '🛩️', '💺', '🛰️', '🚀',
                '🛸', '🚁', '🛶', '⛵', '🚤', '🛥️', '🛳️', '⛴️', '🚢', '⚓', '⛽', '🚧',
                '🚦', '🚥', '🚏', '🗺️', '🗿', '🗽', '🗼', '🏰', '🏯', '🏟️', '🎡', '🎢',
                '🎠', '⛲', '⛱️', '🏖️', '🏝️', '🏜️', '🌋', '⛰️', '🏔️', '🗻', '🏕️', '⛺',
                '🏠', '🏡', '🏘️', '🏚️', '🏗️', '🏭', '🏢', '🏬', '🏣', '🏤', '🏥', '🏦'
            ]},
            'objects': { name: 'Objects', icon: '💡', emojis: [
                '⌚', '📱', '📲', '💻', '⌨️', '🖥️', '🖨️', '🖱️', '🖲️', '🕹️', '🗜️', '💽',
                '💾', '💿', '📀', '📼', '📷', '📸', '📹', '🎥', '📽️', '🎞️', '📞', '☎️',
                '📟', '📠', '📺', '📻', '🎙️', '🎚️', '🎛️', '🧭', '⏱️', '⏲️', '⏰', '🕰️',
                '⌛', '⏳', '📡', '🔋', '🔌', '💡', '🔦', '🕯️', '🪔', '🧯', '🛢️', '💸',
                '💵', '💴', '💶', '💷', '💰', '💳', '💎', '⚖️', '🧰', '🔧', '🔨', '⚒️',
                '🛠️', '⛏️', '🔩', '⚙️', '🧱', '⛓️', '🧲', '🔫', '💣', '🧨', '🪓', '🔪',
                '🗡️', '⚔️', '🛡️', '🚬', '⚰️', '⚱️', '🏺', '🔮', '📿', '🧿', '💈', '⚗️'
            ]},
            'symbols': { name: 'Symbols', icon: '❤️', emojis: [
                '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕',
                '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️', '✝️', '☪️', '🕉️', '☸️',
                '✡️', '🔯', '🕎', '☯️', '☦️', '🛐', '⛎', '♈', '♉', '♊', '♋', '♌',
                '♍', '♎', '♏', '♐', '♑', '♒', '♓', '🆔', '⚛️', '🉑', '☢️', '☣️',
                '📴', '📳', '🈶', '🈚', '🈸', '🈺', '🈷️', '✴️', '🆚', '💮', '🉐', '㊙️',
                '㊗️', '🈴', '🈵', '🈹', '🈲', '🅰️', '🅱️', '🆎', '🆑', '🅾️', '🆘', '❌',
                '⭕', '🛑', '⛔', '📛', '🚫', '💯', '💢', '♨️', '🚷', '🚯', '🚳', '🚱',
                '🔞', '📵', '🚭', '❗', '❕', '❓', '❔', '‼️', '⁉️', '🔅', '🔆', '〽️'
            ]},
            'flags': { name: 'Flags', icon: '🏳️', emojis: [
                '🏁', '🚩', '🎌', '🏴', '🏳️', '🏳️‍🌈', '🏴‍☠️', '🇺🇳', '🇦🇫', '🇦🇽', '🇦🇱', '🇩🇿',
                '🇦🇸', '🇦🇩', '🇦🇴', '🇦🇮', '🇦🇶', '🇦🇬', '🇦🇷', '🇦🇲', '🇦🇼', '🇦🇺', '🇦🇹', '🇦🇿',
                '🇧🇸', '🇧🇭', '🇧🇩', '🇧🇧', '🇧🇾', '🇧🇪', '🇧🇿', '🇧🇯', '🇧🇲', '🇧🇹', '🇧🇴', '🇧🇦',
                '🇧🇼', '🇧🇷', '🇮🇴', '🇻🇬', '🇧🇳', '🇧🇬', '🇧🇫', '🇧🇮', '🇰🇭', '🇨🇲', '🇨🇦', '🇮🇨',
                '🇨🇻', '🇧🇶', '🇰🇾', '🇨🇫', '🇹🇩', '🇨🇱', '🇨🇳', '🇨🇽', '🇨🇨', '🇨🇴', '🇰🇲', '🇨🇬'
            ]}
        };
        
        this.isOpen = false;
        this.pickerElement = null;
    }
    
    toggle(inputElement) {
        if (this.isOpen) {
            this.close();
        } else {
            this.open(inputElement);
        }
    }
    
    open(inputElement) {
        this.inputElement = inputElement;
        this.render();
        this.isOpen = true;
    }
    
    close() {
        if (this.pickerElement) {
            this.pickerElement.remove();
            this.pickerElement = null;
        }
        this.isOpen = false;
    }
    
    render() {
        this.pickerElement = document.createElement('div');
        this.pickerElement.className = 'emoji-picker';
        
        // Create header with search
        const header = document.createElement('div');
        header.className = 'emoji-picker-header';
        header.innerHTML = `
            <input type="text" class="emoji-search" placeholder="Search emoji...">
        `;
        
        // Create category tabs
        const tabs = document.createElement('div');
        tabs.className = 'emoji-picker-tabs';
        for (let catId in this.categories) {
            const cat = this.categories[catId];
            if (catId === 'recent' && cat.emojis.length === 0) continue;
            
            const tab = document.createElement('button');
            tab.className = 'emoji-tab';
            tab.textContent = cat.icon;
            tab.title = cat.name;
            tab.dataset.category = catId;
            tabs.appendChild(tab);
        }
        
        // Create emoji grid
        const grid = document.createElement('div');
        grid.className = 'emoji-picker-grid';
        this.renderCategory('smileys', grid);
        
        this.pickerElement.appendChild(header);
        this.pickerElement.appendChild(tabs);
        this.pickerElement.appendChild(grid);
        
        // Position picker
        this.positionPicker();
        
        // Attach event listeners
        this.attachEventListeners();
        
        document.body.appendChild(this.pickerElement);
    }
    
    renderCategory(categoryId, gridElement) {
        gridElement.innerHTML = '';
        
        const category = this.categories[categoryId];
        if (!category || (categoryId === 'recent' && category.emojis.length === 0)) {
            gridElement.innerHTML = '<div class="no-emojis">No emojis to show</div>';
            return;
        }
        
        category.emojis.forEach(emoji => {
            const emojiBtn = document.createElement('button');
            emojiBtn.className = 'emoji-btn';
            emojiBtn.textContent = emoji;
            emojiBtn.title = emoji;
            emojiBtn.addEventListener('click', () => this.selectEmoji(emoji));
            gridElement.appendChild(emojiBtn);
        });
    }
    
    positionPicker() {
        if (!this.inputElement || !this.pickerElement) return;
        
        const inputRect = this.inputElement.getBoundingClientRect();
        this.pickerElement.style.position = 'absolute';
        this.pickerElement.style.bottom = (window.innerHeight - inputRect.top + 10) + 'px';
        this.pickerElement.style.left = inputRect.left + 'px';
    }
    
    attachEventListeners() {
        // Tab switching
        const tabs = this.pickerElement.querySelectorAll('.emoji-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                const categoryId = tab.dataset.category;
                const grid = this.pickerElement.querySelector('.emoji-picker-grid');
                this.renderCategory(categoryId, grid);
            });
        });
        
        // Set first tab as active
        if (tabs.length > 0) {
            tabs[0].classList.add('active');
        }
        
        // Search functionality
        const searchInput = this.pickerElement.querySelector('.emoji-search');
        searchInput.addEventListener('input', (e) => {
            this.searchEmojis(e.target.value);
        });
        
        // Close on outside click
        setTimeout(() => {
            document.addEventListener('click', this.outsideClickHandler.bind(this));
        }, 0);
    }
    
    outsideClickHandler(e) {
        if (this.pickerElement && !this.pickerElement.contains(e.target) && e.target !== this.inputElement) {
            this.close();
            document.removeEventListener('click', this.outsideClickHandler.bind(this));
        }
    }
    
    searchEmojis(query) {
        if (!query) {
            this.renderCategory('smileys', this.pickerElement.querySelector('.emoji-picker-grid'));
            return;
        }
        
        const grid = this.pickerElement.querySelector('.emoji-picker-grid');
        grid.innerHTML = '';
        
        let found = false;
        for (let catId in this.categories) {
            if (catId === 'recent') continue;
            const category = this.categories[catId];
            
            // Simple search - in a real app, you'd have emoji names/keywords
            category.emojis.forEach(emoji => {
                const emojiBtn = document.createElement('button');
                emojiBtn.className = 'emoji-btn';
                emojiBtn.textContent = emoji;
                emojiBtn.addEventListener('click', () => this.selectEmoji(emoji));
                grid.appendChild(emojiBtn);
                found = true;
            });
        }
        
        if (!found) {
            grid.innerHTML = '<div class="no-emojis">No emojis found</div>';
        }
    }
    
    selectEmoji(emoji) {
        // Save to recent
        this.addToRecent(emoji);
        
        // Call callback
        this.onEmojiSelect(emoji);
        
        // Close picker
        this.close();
    }
    
    addToRecent(emoji) {
        // Remove if already exists
        this.recentEmojis = this.recentEmojis.filter(e => e !== emoji);
        
        // Add to beginning
        this.recentEmojis.unshift(emoji);
        
        // Limit to 30 emojis
        this.recentEmojis = this.recentEmojis.slice(0, 30);
        
        // Update category
        this.categories.recent.emojis = this.recentEmojis;
        
        // Save to localStorage
        this.saveRecentEmojis();
    }
    
    loadRecentEmojis() {
        try {
            const saved = localStorage.getItem('recent_emojis');
            return saved ? JSON.parse(saved) : [];
        } catch (e) {
            return [];
        }
    }
    
    saveRecentEmojis() {
        try {
            localStorage.setItem('recent_emojis', JSON.stringify(this.recentEmojis));
        } catch (e) {
            console.error('Failed to save recent emojis:', e);
        }
    }
}

// Export
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EmojiPicker;
}
