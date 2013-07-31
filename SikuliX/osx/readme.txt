*** Sikuli API 1.0.0 for Mac ***
---------------------------------------

*** Installation
- unzip to any location you like
- the preferred folder name is SikuliX (but need not be)

*** This package is intended for usage from command line 

*** If you just want the Sikuli IDE download and try the Application package

*** Using the contained command script
- you can run the command scripts from any working folder using its absolute or relative path
- use sikli-script (interactive or run scripts) this way:
./sikuli-script (supported options: -h (help), -i (interactive), -r (run a script))

*** Java-Version:
- the stuff is compiled with Java 6 latest version
- it runs on Java 7 and OpenJDK 7
- it is run with the current default Java version on your machine
- use "/usr/libexec/java_home -V" to find out, what you have on your machine
- having more than one Java version on your machine, use option 
  -j 6 to run with your Java 1.6 (Apple)
  -j 7 to run with your Java 1.7 (Oracle)
  -j o to run with OpenJDK 7 (installed in standard place /Library/Java/JavaVirtualMachines)
with the above mentioned command scripts

*** Using sikuli-script.jar in Java programming with IDE's like Netbeans, Eclipse, ...
    or Java based scripting (like Jython, JRuby, Scala, Groovy, Clojure, ...)

look: https://github.com/RaiMan/SikuliX-API/wiki/Usage-in-Java-programming  
