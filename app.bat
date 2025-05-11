@echo off
setlocal EnableDelayedExpansion

set "PHP_EXEC=C:\xampp\php\php.exe"
set "WEBSOCKET_SCRIPT=%CD%\srv_websocket.php"
set "DETECTOR_SCRIPT=%CD%\srv_detector.php"
set "WEBSOCKET_LOG=%CD%\logs\websocket.log"
set "DETECTOR_LOG=%CD%\logs\middleware.log"

if "%1"=="" (
    echo Usage: app.bat [start^|stop^|monitor [-all^|-sockets^|-detector]]
    echo Note: In PowerShell, use .\app.bat to run this script.
    exit /b 1
)

if /i "%1"=="start" (
    echo Starting services...
	
	:: Delete old logs
	del %CD%\logs\*.log
	
    :: Start WebSocket service
    start "FD_SCREEN_WebSocket" /b %PHP_EXEC% %WEBSOCKET_SCRIPT%
    if !ERRORLEVEL! equ 0 (
        echo WebSocket service started.
    ) else (
        echo Failed to start WebSocket service.
    )

    :: Start Change Detector service
    start "FD_SCREEN_ChangeDetector" /b %PHP_EXEC% %DETECTOR_SCRIPT%
    if !ERRORLEVEL! equ 0 (
        echo Change Detector service started.
    ) else (
        echo Failed to start Change Detector service.
    )

    exit /b 0
)

if /i "%1"=="stop" (
    echo Stopping services...

    :: Stop processes listening on port 8081 (WebSocket service)
    echo Stopping WebSocket service on port 8081...
    for /f "tokens=5" %%p in ('netstat -aon ^| findstr :8081') do (
        taskkill /PID %%p /F >nul 2>&1
        if !ERRORLEVEL! equ 0 (
            echo Process with PID %%p stopped.
        ) else (
            echo Failed to stop process with PID %%p.
        )
    )

    :: Note: Detector service port is not specified, adjust if needed
    echo Note: Detector service stopping is not implemented yet due to unspecified port.
    echo If Detector service uses a specific port, add similar logic here.

    exit /b 0
)

if /i "%1"=="monitor" (
    set "FILTER=-all"
    if not "%2"=="" set "FILTER=%2"

    if /i "!FILTER!"=="-all" (
        echo Monitoring all logs...
        type %WEBSOCKET_LOG% %DETECTOR_LOG%
        exit /b 0
    ) else if /i "!FILTER!"=="-sockets" (
        echo Monitoring WebSocket logs...
        type %WEBSOCKET_LOG%
        exit /b 0
    ) else if /i "!FILTER!"=="-detector" (
        echo Monitoring Change Detector logs...
        type %DETECTOR_LOG%
        exit /b 0
    ) else (
        echo Invalid filter. Usage: app.bat monitor [-all^|-sockets^|-detector]
        echo Note: When no arg given, it defaults to -all
        exit /b 1
    )
)

echo Usage: app.bat [start^|stop^|monitor [-all^|-sockets^|-detector]]
echo Note: In PowerShell, use .\app.bat to run this script.
exit /b 1