# ref. https://github.com/TYPO3/typo3/blob/11.5/typo3/sysext/core/ext_tables.sql#L226
CREATE TABLE sys_file_processedfile
(
    processed TINYINT(1) DEFAULT 0 NOT NULL,

    KEY checksum (checksum)
);
