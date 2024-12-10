DROP TABLE IF EXISTS b_auto_manager_smart_process;
CREATE TABLE IF NOT EXISTS b_auto_manager_smart_process (
     SMART_PROCESS_ID INT NOT NULL,
     PRIMARY KEY (SMART_PROCESS_ID)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS b_auto_manager_smart_process_hide_field_fuel_type;
CREATE TABLE IF NOT EXISTS b_auto_manager_smart_process_hide_field_fuel_type (
    VIN VARCHAR(255) NOT NULL,
    FUEL_TYPE INT NOT NULL,
    PRIMARY KEY (VIN)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS b_auto_manager_smart_process_hide_field_year;
CREATE TABLE IF NOT EXISTS b_auto_manager_smart_process_hide_field_year (
    VIN VARCHAR(255) NOT NULL,
    YEAR_OF_MANUFACTURE INT NOT NULL,
    PRIMARY KEY (VIN)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS b_auto_manager_smart_process_hide_field_registration_date;
CREATE TABLE IF NOT EXISTS b_auto_manager_smart_process_hide_field_registration_date (
    VIN VARCHAR(255) NOT NULL,
    REGISTRATION_DATE DATETIME NOT NULL,
    PRIMARY KEY (VIN)
) ENGINE = InnoDB;