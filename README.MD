Loop ->
Ws socket #done
    Server

Http Server create ->
    Controller #done
    Cors #Done
    Middleware #Done
    Routing #done
    RenderStatic files <- Filesystem

Socket Server create ->
    Inject WS
    Upgrade to socket (Ratchet)

Filesystem
    Uploader #done
    LoadFile #done
    Create File 
    Put File Content

Cache
Redis
Socket Server create
Filesystem #done

Orm #Done
Config #done 
DI #done
Logger #done
Ws socket #done
CronJobs #done
Filesystem #done