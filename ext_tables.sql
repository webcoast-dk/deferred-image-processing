create table tx_deferredimageprocessing_file
(
    uid           int(10) unsigned not null auto_increment,
    public_url    varchar(255)     not null,
    storage       int(10) unsigned not null,
    source_file   int(10) unsigned not null,
    task_type     varchar(50)      not null,
    task_name     varchar(50)      not null,
    configuration text             not null,
    checksum      varchar(32)      not null,

    primary key (uid),

    key public_url (public_url),
    key instruction (storage, source_file, task_type, task_name, checksum)
);
