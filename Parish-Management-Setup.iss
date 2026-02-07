[Setup]
AppName=Parish Management System
AppVersion=1.0
AppPublisher=Fr. Bastin - Trichy
AppPublisherURL=mailto:anito123789@gmail.com
AppSupportURL=mailto:anito123789@gmail.com
AppUpdatesURL=mailto:anito123789@gmail.com
DefaultDirName={autopf}\Parish Management System
DefaultGroupName=Parish Management System
AllowNoIcons=yes
LicenseFile=
InfoBeforeFile=
InfoAfterFile=
OutputDir=installer
OutputBaseFilename=Parish-Management-System-Setup
SetupIconFile=assets\app_logo.ico
Compression=lzma
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=admin
ArchitecturesAllowed=x64
ArchitecturesInstallIn64BitMode=x64

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked
Name: "quicklaunchicon"; Description: "{cm:CreateQuickLaunchIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked; OnlyBelowVersion: 6.1; Check: not IsAdminInstallMode

[Files]
Source: "*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs; Excludes: "installer\*,*.iss,INSTALL.bat"

[Icons]
Name: "{group}\Parish Management System"; Filename: "{app}\Launch Parish M.vbs"; WorkingDir: "{app}"; IconFilename: "{app}\assets\app_logo.ico"
Name: "{group}\{cm:UninstallProgram,Parish Management System}"; Filename: "{uninstallexe}"
Name: "{autodesktop}\Parish Management System"; Filename: "{app}\Launch Parish M.vbs"; WorkingDir: "{app}"; IconFilename: "{app}\assets\app_logo.ico"; Tasks: desktopicon
Name: "{userappdata}\Microsoft\Internet Explorer\Quick Launch\Parish Management System"; Filename: "{app}\Launch Parish M.vbs"; WorkingDir: "{app}"; IconFilename: "{app}\assets\app_logo.ico"; Tasks: quicklaunchicon

[Run]
Filename: "{app}\Launch Parish M.vbs"; Description: "{cm:LaunchProgram,Parish Management System}"; Flags: nowait postinstall skipifsilent

[Code]
function InitializeSetup(): Boolean;
var
  ResultCode: Integer;
begin
  // Check if PHP is installed
  if not Exec('php', '-v', '', SW_HIDE, ewWaitUntilTerminated, ResultCode) then
  begin
    if MsgBox('PHP is not installed on your system. Parish Management System requires PHP 7.4 or higher.' + #13#10 + #13#10 + 
              'Would you like to continue the installation? You will need to install PHP manually later.' + #13#10 + #13#10 +
              'Download PHP from: https://windows.php.net/download/', 
              mbConfirmation, MB_YESNO) = IDNO then
    begin
      Result := False;
      Exit;
    end;
  end;
  Result := True;
end;

procedure CurStepChanged(CurStep: TSetupStep);
begin
  if CurStep = ssPostInstall then
  begin
    // Create database directory if it doesn't exist
    if not DirExists(ExpandConstant('{app}\database')) then
      CreateDir(ExpandConstant('{app}\database'));
  end;
end;

[Messages]
WelcomeLabel1=Welcome to the Parish Management System Setup Wizard
WelcomeLabel2=This will install Parish Management System v1.0 on your computer.%n%nDeveloped by Fr. Bastin - Trichy%nEmail: anito123789@gmail.com%n%nIt is recommended that you close all other applications before continuing.