import type { Order, VirtualOrder, Event } from '../types';
import type { WebSocketData } from '../hooks/useWebSocket';

export function updateOrdersFromWebSocket(currentOrders: Order[], newData: WebSocketData): Order[] {
  if (!newData.orders || !Array.isArray(newData.orders)) return currentOrders;
  
  const updatedOrders = [...currentOrders];
  newData.orders.forEach((newOrder) => {
    const index = updatedOrders.findIndex(order => order.AuftragId === newOrder.AuftragId);
    if (index !== -1) {
      updatedOrders[index] = newOrder;
    } else {
      updatedOrders.push(newOrder);
    }
  });
  return updatedOrders;
}

export function updateVirtualOrdersFromWebSocket(currentVirtualOrders: VirtualOrder[], newData: WebSocketData): VirtualOrder[] {
  if (!newData.virtual_orders || !Array.isArray(newData.virtual_orders)) return currentVirtualOrders;
  
  const updatedVirtualOrders = [...currentVirtualOrders];
  newData.virtual_orders.forEach((newVirtualOrder) => {
    const index = updatedVirtualOrders.findIndex(order => order.AuftragId === newVirtualOrder.AuftragId);
    if (index !== -1) {
      updatedVirtualOrders[index] = newVirtualOrder;
    } else {
      updatedVirtualOrders.push(newVirtualOrder);
    }
  });
  return updatedVirtualOrders;
}

export function updateEventsFromWebSocket(currentEvents: Event[], newData: WebSocketData): Event[] {
  if (!newData.events || !Array.isArray(newData.events)) return currentEvents;
  
  const updatedEvents = [...currentEvents];
  newData.events.forEach((newEvent) => {
    const index = updatedEvents.findIndex(event => event.Id === newEvent.Id);
    if (index !== -1) {
      updatedEvents[index] = newEvent;
    } else {
      updatedEvents.push(newEvent);
    }
  });
  return updatedEvents;
}