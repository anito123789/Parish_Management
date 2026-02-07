; Parish Management System - Inno Setup Script
; Developer: Fr. Bastin - Trichy
; Email: anito123789@gmail.com

#define MyAppName "Parish Management System"
#define MyAppVersion "1.0"
#define MyAppPublisher "Fr. Bastin - Trichy"
#define MyAppURL "mailto:anito123789@gmail.com"
#define MyAppExeName "Launch Parish M.bat"

[Setup]
; NOTE: The value of AppId uniquely identifies this application.
AppId={{A1B2C3D4-E5F6-7890-ABCD-EF1234567890}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
AppUpdatesURL={#MyAppURL}
DefaultDirName={autopf}\ParishManagement
DefaultGroupName={#MyAppName}
AllowNoIcons=yes
LicenseFile=LICENSE.txt
InfoBeforeFile=INSTALLATION_GUIDE.md
OutputDir=.
OutputBaseFilename=ParishManagement_v1.0_Setup
SetupIconFile=assets\parish_icon.ico
Compression=lzma
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=admin
UninstallDisplayIcon={app}\assets\parish_icon.ico
UninstallDisplayName={#MyAppName}

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked
Name: "quicklaunchicon"; Description: "{cm:CreateQuickLaunchIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked; OnlyBelowVersion: 6.1; Check: not IsAdminInstallMode

[Files]
Source: "*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs
; NOTE: Don't use "Flags: ignoreversion" on any shared system files

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; IconFilename: "{app}\assets\parish_icon.ico"
Name: "{group}\{cm:UninstallProgram,{#MyAppName}}"; Filename: "{uninstallexe}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; IconFilename: "{app}\assets\parish_icon.ico"; Tasks: desktopicon
Name: "{userappdata}\Microsoft\Internet Explorer\Quick Launch\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; Tasks: quicklaunchicon

[Run]
Filename: "{app}\{#MyAppExeName}"; Description: "{cm:LaunchProgram,{#StringChange(MyAppName, '&', '&&')}}"; Flags: shellexec postinstall skipifsilent

[Code]
function InitializeSetup(): Boolean;
var
  ResultCode: Integer;
begin
  Result := True;
  
  // Check if PHP is installed
  if not FileExists('C:\php\php.exe') then
  begin
    if MsgBox('PHP is not detected on your system. This application requires PHP 7.4 or higher to run.' + #13#10 + #13#10 + 
              'Would you like to continue installation? (You will need to install PHP manually)', 
              mbConfirmation, MB_YESNO) = IDNO then
    begin
      Result := False;
    end;
  end;
end;

procedure CurStepChanged(CurStep: TSetupStep);
begin
  if CurStep = ssPostInstall then
  begin
    // Create database directory if it doesn't exist
    if not DirExists(ExpandConstant('{app}\database')) then
      CreateDir(ExpandConstant('{app}\database'));
      
    // Create backups directory
    if not DirExists(ExpandConstant('{app}\backups')) then
      CreateDir(ExpandConstant('{app}\backups'));
      
    // Create uploads directory
    if not DirExists(ExpandConstant('{app}\uploads')) then
      CreateDir(ExpandConstant('{app}\uploads'));
  end;
end;

[UninstallDelete]
Type: filesandordirs; Name: "{app}\database"
Type: filesandordirs; Name: "{app}\backups"
Type: filesandordirs; Name: "{app}\uploads"
Type: filesandordirs; Name: "{app}\qrcodes"
