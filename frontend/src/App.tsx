import React from 'react';
import './App.css';
import Dashboard from './pages/Dashboard';
import { BrowserRouter as Router, Route, Routes } from 'react-router-dom';
import { AppProvider } from './AppContext';

function App() {
  return (
    <div className="App">
      <header className="App-header"></header>
      <main>
        <AppProvider>
          <Router>
            <Routes>
              <Route path="/" element={<Dashboard />} />
              {/* Weitere Routen können hier hinzugefügt werden */}
            </Routes>
          </Router>
        </AppProvider>
      </main>
    </div>
  );
}

export default App;
