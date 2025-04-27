$(function() {
    // Initialize global state
    window.ordersState = utils.loadOrders();
    console.log('Initial Orders State:', window.ordersState);

    // Initialize WebSocket
    websocket.connectWebSocket();

    // Initialize UI
    $(document).ready(function() {
        ui.initUI();
    });
});