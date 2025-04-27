@echo off
setlocal EnableDelayedExpansion

REM Define paths
set APP_ROOT=%CD%
set PHP_EXEC=C:\xampp\php\php.exe
set NSSM_EXEC=%APP_ROOT%\nssm.exe
set WEBSOCKET_SCRIPT=%APP_ROOT%\websocket_server.php
set DETECTOR_SCRIPT=%APP_ROOT%\change_detector.php
set WEBSOCKET_LOG=%APP_ROOT%\logs\websocket.log
set DETECTOR_LOG=%APP_ROOT%\logs\middleware.log

REM Define service names
set WEBSOCKET_SERVICE=FD_SCREEN_WebSocket
set DETECTOR_SERVICE=FD_SCREEN_ChangeDetector

REM Command argument
set ACTION=%1

if "%ACTION%"=="" (
    echo Usage: app.bat [start^|stop^|restart^|monitor^|monitor-live]
    exit /b 1
)

if /I "%ACTION%"=="start" (
    echo Starting services...

    REM Install and start WebSocket service if not already installed
    %NSSM_EXEC% status %WEBSOCKET_SERVICE% >nul 2>&1
    if errorlevel 1 (
        echo Installing WebSocket service...
        %NSSM_EXEC% install %WEBSOCKET_SERVICE% "%PHP_EXEC%" "%WEBSOCKET_SCRIPT%"
        %NSSM_EXEC% set %WEBSOCKET_SERVICE% AppDirectory "%APP_ROOT%"
        %NSSM_EXEC% set %WEBSOCKET_SERVICE% AppStdout "%WEBSOCKET_LOG%"
        %NSSM_EXEC% set %WEBSOCKET_SERVICE% AppStderr "%WEBSOCKET_LOG%"
    )
    %NSSM_EXEC% start %WEBSOCKET_SERVICE%
    if errorlevel 0 (
        echo WebSocket service started.
    ) else (
        echo Failed to start WebSocket service.
    )

    REM Install and start Change Detector service if not already installed
    %NSSM_EXEC% status %DETECTOR_SERVICE% >nul 2>&1
    if errorlevel 1 (
        echo Installing Change Detector service...
        %NSSM_EXEC% install %DETECTOR_SERVICE% "%PHP_EXEC%" "%DETECTOR_SCRIPT%"
        %NSSM_EXEC% set %DETECTOR_SERVICE% AppDirectory "%APP_ROOT%"
        %NSSM_EXEC% set %DETECTOR_SERVICE% AppStdout "%DETECTOR_LOG%"
        %NSSM_EXEC% set %DETECTOR_SERVICE% AppStderr "%DETECTOR_LOG%"
    )
    %NSSM_EXEC% start %DETECTOR_SERVICE%
    if errorlevel 0 (
        echo Change Detector service started.
    ) else (
        echo Failed to start Change Detector service.
    )

) else if /I "%ACTION%"=="stop" (
    echo Stopping services...

    %NSSM_EXEC% stop %WEBSOCKET_SERVICE%
    if errorlevel 0 (
        echo WebSocket service stopped.
    ) else (
        echo WebSocket service not running.
    )

    %NSSM_EXEC% stop %DETECTOR_SERVICE%
    if errorlevel 0 (
        echo Change Detector service stopped.
    ) else (
        echo Change Detector service not running.
    )

) else if /I "%ACTION%"=="restart" (
    echo Restarting services...

    %NSSM_EXEC% stop %WEBSOCKET_SERVICE%
    %NSSM_EXEC% start %WEBSOCKET_SERVICE%
    if errorlevel 0 (
        echo WebSocket service restarted.
    ) else (
        echo Failed to restart WebSocket service.
    )

    %NSSM_EXEC% stop %DETECTOR_SERVICE%
    %NSSM_EXEC% start %DETECTOR_SERVICE%
    if errorlevel 0 (
        echo Change Detector service restarted.
    ) else (
        echo Failed to restart Change Detector service.
    )

) else if /I "%ACTION%"=="monitor" (
    set FILTER=%2
    if "%FILTER%"=="" set FILTER=-all

    if /I "%FILTER%"=="-all" (
        echo Monitoring all services...
        echo --- WebSocket Logs ---
        type "%WEBSOCKET_LOG%"
        echo --- Change Detector Logs ---
        type "%DETECTOR_LOG%"
    ) else if /I "%FILTER%"=="-sockets" (
        echo Monitoring WebSocket service...
        type "%WEBSOCKET_LOG%"
    ) else if /I "%FILTER%"=="-detector" (
        echo Monitoring Change Detector service...
        type "%DETECTOR_LOG%"
    ) else (
        echo Invalid filter. Usage: app.bat monitor [-all^|-sockets^|-detector]
        exit /b 1
    )

) else if /I "%ACTION%"=="monitor-live" (
    set FILTER=%2
    if "%FILTER%"=="" set FILTER=-all

    if /I "%FILTER%"=="-all" (
        echo Live monitoring all services...
        echo Press Ctrl+C to stop monitoring.
        powershell -Command "Get-Content '%WEBSOCKET_LOG%' -Wait; Get-Content '%DETECTOR_LOG%' -Wait"
    ) else if /I "%FILTER%"=="-sockets" (
        echo Live monitoring WebSocket service...
        echo Press Ctrl+C to stop monitoring.
        powershell -Command "Get-Content '%WEBSOCKET_LOG%' -Wait"
    ) else if /I "%FILTER%"=="-detector" (
        echo Live monitoring Change Detector service...
        echo Press Ctrl+C to stop monitoring.
        powershell -Command "Get-Content '%DETECTOR_LOG%' -Wait"
    ) else (
        echo Invalid filter. Usage: app.bat monitor-live [-all^|-sockets^|-detector]
        exit /b 1
    )

) else (
    echo Invalid command. Usage: app.bat [start^|stop^|restart^|monitor^|monitor-live]
    exit /b 1
)

endlocal