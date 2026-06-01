-- Script de Actualización y Migración de Base de Datos - Fase 1
-- Diseñado para MySQL / MariaDB (S.Taller)
-- Comentarios estrictamente en español.

USE taller_mecanico;

-- 1. Crear la tabla relacional de especialidades para cumplir con la Primera Forma Normal (1NF)
CREATE TABLE IF NOT EXISTS trabajador_especialidades (
    trabajador_id INT NOT NULL,
    especialidad VARCHAR(100) NOT NULL,
    PRIMARY KEY (trabajador_id, especialidad),
    FOREIGN KEY (trabajador_id) REFERENCES trabajadores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Procedimiento almacenado temporal para migrar los datos de la columna de texto a la tabla relacional
DROP PROCEDURE IF EXISTS migrar_especialidades;

DELIMITER //

CREATE PROCEDURE migrar_especialidades()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE t_id INT;
    DECLARE t_esp TEXT;
    
    -- Cursor para obtener a todos los trabajadores y sus especialidades actuales en formato CSV
    DECLARE cur CURSOR FOR SELECT id, especialidades FROM trabajadores;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO t_id, t_esp;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Separar y limpiar cada especialidad delimitada por comas
        IF t_esp IS NOT NULL AND TRIM(t_esp) != '' THEN
            BEGIN
                DECLARE comma_pos INT;
                DECLARE cur_esp VARCHAR(100);
                DECLARE rest_esp TEXT;
                SET rest_esp = t_esp;

                split_loop: LOOP
                    SET comma_pos = LOCATE(',', rest_esp);
                    IF comma_pos > 0 THEN
                        SET cur_esp = TRIM(SUBSTRING(rest_esp, 1, comma_pos - 1));
                        SET rest_esp = SUBSTRING(rest_esp, comma_pos + 1);
                    ELSE
                        SET cur_esp = TRIM(rest_esp);
                        SET rest_esp = '';
                    END IF;

                    -- Insertar la especialidad limpia si no está vacía
                    IF cur_esp != '' THEN
                        INSERT IGNORE INTO trabajador_especialidades (trabajador_id, especialidad)
                        VALUES (t_id, cur_esp);
                    END IF;

                    IF rest_esp = '' THEN
                        LEAVE split_loop;
                    END IF;
                END LOOP split_loop;
            END;
        END IF;
    END LOOP read_loop;

    CLOSE cur;
END //

DELIMITER ;

-- Ejecutar la migración automática de datos
CALL migrar_especialidades();

-- Eliminar el procedimiento de migración tras su uso
DROP PROCEDURE IF EXISTS migrar_especialidades;

-- 3. Modificaciones en la tabla 'trabajadores'
-- Eliminar el campo multivaluado 'especialidades' (ahora normalizado en 1NF) y el concepto de 'costo_hora'
ALTER TABLE trabajadores DROP COLUMN especialidades;
ALTER TABLE trabajadores DROP COLUMN costo_hora;

-- 4. Modificaciones en la tabla 'ordenes_trabajo'
-- Agregar el campo numérico para registro manual de mano de obra
ALTER TABLE ordenes_trabajo ADD COLUMN costo_mano_obra DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER observaciones;
-- Expandir la columna estado para admitir el estado 'pagada'
ALTER TABLE ordenes_trabajo MODIFY COLUMN estado ENUM('pendiente', 'en_diagnostico', 'presupuestado', 'en_progreso', 'terminado', 'entregado', 'anulado', 'cerrado', 'pagada') DEFAULT 'pendiente';

-- 5. Eliminar índices redundantes en columnas que ya cuentan con restricciones UNIQUE
DROP INDEX idx_autos_placa ON autos;
DROP INDEX idx_ordenes_codigo ON ordenes_trabajo;
DROP INDEX idx_inventario_sku ON inventario;
