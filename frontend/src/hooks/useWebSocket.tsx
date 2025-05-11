import { useEffect, useState, useRef, useCallback } from 'react';

export interface WebSocketData {
  orders?: Array<{
    AuftragId: string;
    AuftragsNr: string;
    Status: number;
    KundenMatchcode: string;
    Liefertermin: string;
    BestellNr?: string;
  }>;
  virtual_orders?: Array<{
    AuftragId: string;
    AuftragsNr: string;
    Status: number;
    Beschreibung: string;
    Typ: string;
  }>;
  events?: Array<{
    Id: string;
    Titel: string;
    Datum: string;
    Beschreibung: string;
    Kategorie?: string;
  }>;
}

export interface WebSocketHook {
  data: WebSocketData | null;
  isConnected: boolean;
}

export default function useWebSocket(url: string = process.env.REACT_APP_WEBSOCKET_URL || 'ws://127.0.0.1:8081'): WebSocketHook {
  const [data, setData] = useState<WebSocketData | null>(null);
  const [isConnected, setIsConnected] = useState<boolean>(false);
  const socketRef = useRef<WebSocket | null>(null);
  const reconnectAttempts = useRef<number>(0);
  const maxReconnectAttempts = 5;
  const reconnectInterval = 3000; // 3 seconds

  const connect = useCallback(() => {
    if (socketRef.current && socketRef.current.readyState === WebSocket.OPEN) {
      return; // Connection already open
    }

    socketRef.current = new WebSocket(url);

    socketRef.current.onopen = () => {
      console.log(`WebSocket-Verbindung geöffnet: ${url}`);
      setIsConnected(true);
      reconnectAttempts.current = 0; // Reset reconnect attempts on successful connection
    };

    socketRef.current.onmessage = (event) => {
      try {
        const receivedData = JSON.parse(event.data);
        console.log('WebSocket-Daten empfangen:', receivedData);
        setData(receivedData as WebSocketData);
      } catch (error) {
        console.error('Fehler beim Parsen der WebSocket-Daten:', error);
        setData(null); // Reset to null on parse error
      }
    };

    socketRef.current.onerror = (error) => {
      console.error('WebSocket-Fehler:', error);
      console.log('WebSocket URL:', url);
      console.log('WebSocket readyState:', socketRef.current?.readyState);
      setIsConnected(false);
    };

    socketRef.current.onclose = (event) => {
      console.log(`WebSocket-Verbindung geschlossen: Code=${event.code}, Reason=${event.reason}`);
      setIsConnected(false);
      if (reconnectAttempts.current < maxReconnectAttempts) {
        reconnectAttempts.current += 1;
        console.log(`Versuche erneut zu verbinden (${reconnectAttempts.current}/${maxReconnectAttempts})...`);
        setTimeout(connect, reconnectInterval);
      } else {
        console.error('Maximale Wiederverbindungsversuche erreicht. Bitte überprüfen Sie den Server.');
      }
    };
  }, [url, reconnectInterval, maxReconnectAttempts]); // Add dependencies

  useEffect(() => {
    connect();

    return () => {
      // Only close the connection if it's not already closed
      if (socketRef.current && socketRef.current.readyState === WebSocket.OPEN) {
        socketRef.current.close(1000, 'Component unmounted');
        socketRef.current = null;
      }
    };
  }, [url, connect]); // Re-run effect if URL or connect changes

  return { data, isConnected }; // Ensure the function returns the required object
}