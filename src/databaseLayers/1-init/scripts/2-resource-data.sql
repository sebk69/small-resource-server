/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

CREATE TABLE resource_data (
    id VARCHAR(255) PRIMARY KEY,
    id_resource VARCHAR(255) NOT NULL,
    selector VARCHAR(255) NOT NULL,
    locked TINYINT(1) NOT NULL DEFAULT 0,
    data LONGTEXT NOT NULL,
    CONSTRAINT fk_resource_data_resource
       FOREIGN KEY (id_resource) REFERENCES resource(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE UNIQUE INDEX ux_resource_data_name_selector
    ON resource_data(id_resource, selector);