$(function() {
    utils.loadOrders().then(state => {
        window.ordersState = state;
        console.log('Initial Orders State:', window.ordersState);

        $(document).ready(function() {
            websocket.connectWebSocket();
            ui.initUI();
        });
    }).catch(error => {
        console.error('Failed to initialize orders state:', error);
        window.ordersState = { orders: {}, virtualOrders: {} };

        $(document).ready(function() {
            websocket.connectWebSocket();
            ui.initUI();
        });
    });
});