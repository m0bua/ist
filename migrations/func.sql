DROP FUNCTION IF EXISTS b64dec;
DELIMITER //
CREATE FUNCTION b64dec(b64 TEXT, ftype CHAR(1))
RETURNS DECIMAL(10,3)
DETERMINISTIC
BEGIN
    DECLARE strt INT;
    DECLARE len INT;
    DECLARE divisor INT;
    CASE ftype
        WHEN 'V' THEN SET strt = 1; SET len = 2; SET divisor = 10;
        WHEN 'A' THEN SET strt = 3; SET len = 3; SET divisor = 1000;
        WHEN 'W' THEN SET strt = 6; SET len = 3; SET divisor = 1000;
        ELSE
            RETURN NULL;
    END CASE;
    RETURN CONV(HEX(SUBSTR(FROM_BASE64(b64), strt, len)), 16, 10) / divisor;
END //
DELIMITER ;
