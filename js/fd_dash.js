$(function() {
    // Initialize global state asynchronously
    utils.loadOrders().then(state => {
        window.ordersState = state;
        console.log('Initial Orders State:', window.ordersState);

        // Initialize WebSocket
        websocket.connectWebSocket();

        // Initialize UI
        $(document).ready(function() {
            ui.initUI();
        });
    }).catch(error => {
        console.error('Failed to initialize orders state:', error);
        window.ordersState = { orders: {}, virtualOrders: {} }; // Fallback
    });
});