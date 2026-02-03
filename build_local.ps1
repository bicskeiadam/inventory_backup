# Set JAVA_HOME and Path
$env:JAVA_HOME = "C:\Program Files\Microsoft\jdk-17.0.17.10-hotspot"
$env:Path = "$env:JAVA_HOME\bin;" + $env:Path

# Set ANDROID_HOME (User Provided Path)
$env:ANDROID_HOME = "C:\Users\adamb\AppData\Local\Android"

# Add tools to PATH
# Order matters: platform-tools, then cmdline-tools
$env:Path = "$env:ANDROID_HOME\platform-tools;$env:ANDROID_HOME\cmdline-tools\latest\bin;$env:Path"

# Verify
Write-Host "Verifying environment..."
java -version
Write-Host "ANDROID_HOME: $env:ANDROID_HOME"
Write-Host "Checking adb..."
Get-Command adb | Select-Object Source

# Run Build
Write-Host "Starting Local Build via Gradle (expo run:android)..."
npx expo run:android --device
