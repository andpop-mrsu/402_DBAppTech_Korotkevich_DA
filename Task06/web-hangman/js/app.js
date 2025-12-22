/**
 * Main application entry point
 */
import { GameController } from './controllers/GameController.js';
import { View } from './views/View.js';

// Make controller globally available for button onclick handlers
window.gameController = null;

document.addEventListener('DOMContentLoaded', async () => {
    try {
        // Initialize components
        const view = new View();
        const controller = new GameController(view);
        
        // Make controller available globally
        window.gameController = controller;
        
        // Initialize view
        view.initialize();
        
        // Initialize controller
        await controller.initialize();
        
        // Show welcome message
        view.showMessage('Добро пожаловать в игру "Виселица"! Начните новую игру.', 'info');
        
        console.log('Hangman game initialized successfully!');
    } catch (error) {
        console.error('Failed to initialize game:', error);
        alert('Не удалось инициализировать игру. Пожалуйста, проверьте консоль для деталей.');
    }
});

// Service Worker для оффлайн работы
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(error => {
            console.log('Service Worker registration failed:', error);
        });
    });
}