Option Explicit

Dim fileSystem, shell, phpPath, toolsPath, projectPath, xamppPath, opensslConfig, workerPath, command, exitCode
Set fileSystem = CreateObject("Scripting.FileSystemObject")
Set shell = CreateObject("WScript.Shell")

If WScript.Arguments.Count < 1 Then WScript.Quit 2

phpPath = WScript.Arguments(0)
toolsPath = fileSystem.GetParentFolderName(WScript.ScriptFullName)
projectPath = fileSystem.GetParentFolderName(toolsPath)
xamppPath = fileSystem.GetParentFolderName(fileSystem.GetParentFolderName(phpPath))
opensslConfig = fileSystem.BuildPath(xamppPath, "apache\conf\openssl.cnf")
workerPath = fileSystem.BuildPath(toolsPath, "send-push-reminders.php")

If Not fileSystem.FileExists(phpPath) Then WScript.Quit 2
If Not fileSystem.FileExists(workerPath) Then WScript.Quit 3
If Not fileSystem.FileExists(opensslConfig) Then WScript.Quit 4

shell.CurrentDirectory = projectPath
shell.Environment("PROCESS")("OPENSSL_CONF") = opensslConfig
command = Chr(34) & phpPath & Chr(34) & " " & Chr(34) & workerPath & Chr(34)
exitCode = shell.Run(command, 0, True)
WScript.Quit exitCode
