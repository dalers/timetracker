# Resources

* [TimeTracker Wiki](https://github.com/dalers/timetracker/wiki) - TimeTracker documentation (needs updating).

# Reporting Issues

* Report issues in the [Issue Tracker](https://github.com/dalers/timetracker/issues).

Report security issues like other issues.

# Setting up a Dev Environment

Perform a manual install of a web server, PHP, database server, and TimeTracker.

The recommended application server stack is WSL Ubuntu with Apache, 8.2 and MariaDb.

Docker has not been maintained since the end of 2023 but at the time docker and docker-compose had to be installed first before running a dev instance:

```bash
docker-compose up
```
Navigate to: http://localhost:8080 to use Time Tracker. Default credentials for initial login are:
```
usr: admin
psw: secret
```

