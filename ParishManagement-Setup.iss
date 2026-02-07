; Parish Management System - Inno Setup Script
; Developer: Fr. Bastin - Trichy, Tamil Nadu, India
; Version: 1.0

#define MyAppName "Parish Management System"
#define MyAppVersion "1.0"
#define MyAppPublisher "Fr. Bastin - Trichy"
#define MyAppURL "mailto:anito123789@gmail.com"
#define MyAppExeName "Launch Parish M.vbs"

[Setup]
AppId={{8F9A2B3C-4D5E-6F7A-8B9C-0D1E2F3A4B5C}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
AppUpdatesURL={#MyAppURL}
DefaultDirName={autopf}\Parish Management
DefaultGroupName={#MyAppName}
AllowNoIcons=yes
OutputDir=.
OutputBaseFilename=ParishManagement_v1.0_Setup
Compression=lzma2/max
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=admin
SetupIconFile=assets\parish_icon.ico
UninstallDisplayIcon={app}\assets\parish_icon.ico
DisableProgramGroupPage=yes
DisableWelcomePage=no
LicenseFile=LICENSE.txt
InfoBeforeFile=README.md

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"

[Files]
Source: "*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs; Excludes: "*.iss,ParishManagement_v*.exe,php\*"
Source: "php\*"; DestDir: "{app}\php"; Flags: ignoreversion recursesubdirs createallsubdirs; Check: not PHPInstalled

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; IconFilename: "{app}\assets\parish_icon.ico"
Name: "{group}\Uninstall {#MyAppName}"; Filename: "{uninstallexe}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; IconFilename: "{app}\assets\parish_icon.ico"; Tasks: desktopicon

[Run]
Filename: "{app}\{#MyAppExeName}"; Description: "{cm:LaunchProgram,{#StringChange(MyAppName, '&', '&&')}}"; Flags: nowait postinstall skipifsilent shellexec

[Code]
var
  PHPPage: TInputOptionWizardPage;
  PHPPath: String;

function PHPInstalled: Boolean;
var
  PHPVer: String;
begin
  Result := False;
  if RegQueryStringValue(HKLM, 'SOFTWARE\PHP', 'InstallDir', PHPPath) then
    Result := True
  else if RegQueryStringValue(HKCU, 'SOFTWARE\PHP', 'InstallDir', PHPPath) then
    Result := True
  else
  begin
    PHPPath := GetEnv('PHP_HOME');
    if PHPPath <> '' then
      Result := True
    else
    begin
      if FileExists('C:\php\php.exe') then
      begin
        PHPPath := 'C:\php';
        Result := True;
      end
      else if FileExists('C:\xampp\php\php.exe') then
      begin
        PHPPath := 'C:\xampp\php';
        Result := True;
      end;
    end;
  end;
end;

procedure InitializeWizard;
begin
  PHPPage := CreateInputOptionPage(wpWelcome,
    'PHP Detection', 'Checking for PHP installation',
    'The installer will check if PHP is installed on your system.',
    False, False);
  PHPPage.Add('Use system PHP (if available)');
  PHPPage.Add('Install bundled PHP');
  
  if PHPInstalled then
  begin
    PHPPage.Values[0] := True;
    PHPPage.Values[1] := False;
  end
  else
  begin
    PHPPage.Values[0] := False;
    PHPPage.Values[1] := True;
  end;
end;

function NextButtonClick(CurPageID: Integer): Boolean;
begin
  Result := True;
  if CurPageID = PHPPage.ID then
  begin
    if PHPPage.Values[0] and not PHPInstalled then
    begin
      MsgBox('PHP is not installed on your system. Please select "Install bundled PHP" option.', mbError, MB_OK);
      Result := False;
    end;
  end;
end;

procedure CurStepChanged(CurStep: TSetupStep);
var
  ResultCode: Integer;
begin
  if CurStep = ssPostInstall then
  begin
    // Create database directory if not exists
    if not DirExists(ExpandConstant('{app}\database')) then
      CreateDir(ExpandConstant('{app}\database'));
    
    // Create uploads directory if not exists
    if not DirExists(ExpandConstant('{app}\uploads')) then
      CreateDir(ExpandConstant('{app}\uploads'));
      
    // Create qr_codes directory if not exists
    if not DirExists(ExpandConstant('{app}\qr_codes')) then
      CreateDir(ExpandConstant('{app}\qr_codes'));
  end;
end;

[UninstallDelete]
Type: filesandordirs; Name: "{app}\database"
Type: filesandordirs; Name: "{app}\uploads"
Type: filesandordirs; Name: "{app}\qr_codes"

[Messages]
WelcomeLabel2=This will install [name/ver] on your computer.%n%nParish Management System is a comprehensive solution for managing parish records, sacraments, families, and financial transactions.%n%nDeveloped by Fr. Bastin - Trichy, Tamil Nadu, India
FinishedHeadingLabel=Completing the [name] Setup Wizard
FinishedLabelNoIcons=Setup has finished installing [name] on your computer. The application can be launched by clicking the desktop icon.
FinishedLabel=Setup has finished installing [name] on your computer. The application can be launched by selecting the installed icons.
