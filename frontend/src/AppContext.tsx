import React, { createContext, useContext, useState, useEffect } from 'react';
import type { ReactNode } from 'react';
import useWebSocket from './hooks/useWebSocket';
import type { WebSocketHook } from './hooks/useWebSocket';
import { arrayMove } from '@dnd-kit/sortable';
import { updateOrdersFromWebSocket, updateVirtualOrdersFromWebSocket, updateEventsFromWebSocket } from './utils/webSocketUtils';
import type { VirtualOrder, Event } from './types';

import type { Order } from './types';

interface AppContextType {
  orders: Order[];
  virtualOrders: VirtualOrder[];
  events: Event[];
  isConnected: boolean;
  updateOrderStatus: (auftragId: string, newStatus: number) => void;
  reorderOrders: (auftragId: string, newIndex: number, status: number) => void;
}

const AppContext = createContext<AppContextType | undefined>(undefined);

// eslint-disable-next-line react-refresh/only-export-components
export const useAppContext = () => {
  const context = useContext(AppContext);
  if (context === undefined) {
    throw new Error('useAppContext must be used within an AppProvider');
  }
  return context;
};

interface AppProviderProps {
  children: ReactNode;
}

export const AppProvider: React.FC<AppProviderProps> = ({ children }) => {
  const [orders, setOrders] = useState<Order[]>([]);
  const [virtualOrders, setVirtualOrders] = useState<VirtualOrder[]>([]);
  const [events, setEvents] = useState<Event[]>([]);
  
  const { data, isConnected }: WebSocketHook = useWebSocket('ws://127.0.0.1:8081');

  useEffect(() => {
    if (data) {
      setOrders(prevOrders => updateOrdersFromWebSocket(prevOrders, data));
      setVirtualOrders(prevVirtualOrders => updateVirtualOrdersFromWebSocket(prevVirtualOrders, data));
      setEvents(prevEvents => updateEventsFromWebSocket(prevEvents, data));
    }
  }, [data]);

  const updateOrderStatus = (auftragId: string, newStatus: number) => {
    setOrders(prevOrders => {
      const updatedOrders = [...prevOrders];
      const index = updatedOrders.findIndex(order => order.AuftragId === auftragId);
      if (index !== -1) {
        updatedOrders[index].Status = newStatus;
      }
      return updatedOrders;
    });
  };

  const reorderOrders = (auftragId: string, newIndex: number, status: number) => {
    setOrders(prevOrders => {
      const filteredOrders = prevOrders.filter(order => order.Status === status);
      const otherOrders = prevOrders.filter(order => order.Status !== status);
      const activeIndex = filteredOrders.findIndex(order => order.AuftragId === auftragId);
      if (activeIndex === -1) return prevOrders;
      const reorderedOrders = arrayMove(filteredOrders, activeIndex, newIndex);
      return otherOrders.concat(reorderedOrders);
    });
  };

  return (
    <AppContext.Provider value={{ orders, virtualOrders, events, isConnected, updateOrderStatus, reorderOrders }}>
      {children}
    </AppContext.Provider>
  );
};