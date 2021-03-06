-- Sally Database Dump Version 0.6
-- Prefix sly_

CREATE TABLE sly_article (id NUMBER(10) NOT NULL, clang NUMBER(10) NOT NULL, re_id NUMBER(10) NOT NULL, name VARCHAR2(255) NOT NULL, catname VARCHAR2(255) NOT NULL, catpos NUMBER(10) NOT NULL, attributes TEXT NOT NULL, startpage NUMBER(1) NOT NULL, pos NUMBER(10) NOT NULL, path VARCHAR2(255) NOT NULL, status NUMBER(10) NOT NULL, type VARCHAR2(64) NOT NULL, createdate NUMBER(10) NOT NULL, updatedate NUMBER(10) NOT NULL, createuser VARCHAR2(255) NOT NULL, updateuser VARCHAR2(255) NOT NULL, revision NUMBER(10) DEFAULT 0 NOT NULL, PRIMARY KEY(id, clang));
CREATE TABLE sly_article_slice (id NUMBER(10) NOT NULL, clang NUMBER(10) NOT NULL, slot VARCHAR2(64) NOT NULL, pos NUMBER(10) NOT NULL, slice_id NUMBER(10) DEFAULT 0 NOT NULL, article_id NUMBER(10) NOT NULL, createdate NUMBER(10) NOT NULL, updatedate NUMBER(10) NOT NULL, createuser VARCHAR2(255) NOT NULL, updateuser VARCHAR2(255) NOT NULL, revision NUMBER(10) DEFAULT 0 NOT NULL, PRIMARY KEY(id));
DECLARE
  constraints_Count NUMBER;
BEGIN
  SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count FROM USER_CONSTRAINTS WHERE TABLE_NAME = 'SLY_ARTICLE_SLICE' AND CONSTRAINT_TYPE = 'P';
  IF constraints_Count = 0 OR constraints_Count = '' THEN
    EXECUTE IMMEDIATE 'ALTER TABLE SLY_ARTICLE_SLICE ADD CONSTRAINT SLY_ARTICLE_SLICE_AI_PK PRIMARY KEY (id)';
  END IF;
END;
CREATE SEQUENCE SLY_ARTICLE_SLICE_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1;
CREATE TRIGGER SLY_ARTICLE_SLICE_AI_PK
   BEFORE INSERT
   ON SLY_ARTICLE_SLICE
   FOR EACH ROW
DECLARE
   last_Sequence NUMBER;
   last_InsertID NUMBER;
BEGIN
   SELECT SLY_ARTICLE_SLICE_SEQ.NEXTVAL INTO :NEW.id FROM DUAL;
   IF (:NEW.id IS NULL OR :NEW.id = 0) THEN
      SELECT SLY_ARTICLE_SLICE_SEQ.NEXTVAL INTO :NEW.id FROM DUAL;
   ELSE
      SELECT NVL(Last_Number, 0) INTO last_Sequence
        FROM User_Sequences
       WHERE Sequence_Name = 'SLY_ARTICLE_SLICE_SEQ';
      SELECT :NEW.id INTO last_InsertID FROM DUAL;
      WHILE (last_InsertID > last_Sequence) LOOP
         SELECT SLY_ARTICLE_SLICE_SEQ.NEXTVAL INTO last_Sequence FROM DUAL;
      END LOOP;
   END IF;
END;
CREATE INDEX find_article ON sly_article_slice (article_id, clang);
CREATE TABLE sly_clang (id NUMBER(10) NOT NULL, name VARCHAR2(255) NOT NULL, locale VARCHAR2(5) NOT NULL, revision NUMBER(10) DEFAULT 0 NOT NULL, PRIMARY KEY(id));
DECLARE
  constraints_Count NUMBER;
BEGIN
  SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count FROM USER_CONSTRAINTS WHERE TABLE_NAME = 'SLY_CLANG' AND CONSTRAINT_TYPE = 'P';
  IF constraints_Count = 0 OR constraints_Count = '' THEN
    EXECUTE IMMEDIATE 'ALTER TABLE SLY_CLANG ADD CONSTRAINT SLY_CLANG_AI_PK PRIMARY KEY (id)';
  END IF;
END;
CREATE SEQUENCE SLY_CLANG_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1;
CREATE TRIGGER SLY_CLANG_AI_PK
   BEFORE INSERT
   ON SLY_CLANG
   FOR EACH ROW
DECLARE
   last_Sequence NUMBER;
   last_InsertID NUMBER;
BEGIN
   SELECT SLY_CLANG_SEQ.NEXTVAL INTO :NEW.id FROM DUAL;
   IF (:NEW.id IS NULL OR :NEW.id = 0) THEN
      SELECT SLY_CLANG_SEQ.NEXTVAL INTO :NEW.id FROM DUAL;
   ELSE
      SELECT NVL(Last_Number, 0) INTO last_Sequence
        FROM User_Sequences
       WHERE Sequence_Name = 'SLY_CLANG_SEQ';
      SELECT :NEW.id INTO last_InsertID FROM DUAL;
      WHILE (last_InsertID > last_Sequence) LOOP
         SELECT SLY_CLANG_SEQ.NEXTVAL INTO last_Sequence FROM DUAL;
      END LOOP;
   END IF;
END;
CREATE TABLE sly_file (id NUMBER(10) NOT NULL, re_file_id NUMBER(10) NOT NULL, category_id NUMBER(10) NOT NULL, attributes TEXT NULL, filetype VARCHAR2(255) NOT NULL, filename VARCHAR2(255) NOT NULL, originalname VARCHAR2(255) NOT NULL, filesize NUMBER(10) NOT NULL, width NUMBER(10) NOT NULL, height NUMBER(10) NOT NULL, title VARCHAR2(255) NOT NULL, createdate NUMBER(10) NOT NULL, updatedate NUMBER(10) NOT NULL, createuser VARCHAR2(255) NOT NULL, updateuser VARCHAR2(255) NOT NULL, revision NUMBER(10) DEFAULT 0 NOT NULL, PRIMARY KEY(id));
DECLARE
  constraints_Count NUMBER;
BEGIN
  SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count FROM USER_CONSTRAINTS WHERE TABLE_NAME = 'SLY_FILE' AND CONSTRAINT_TYPE = 'P';
  IF constraints_Count = 0 OR constraints_Count = '' THEN
    EXECUTE IMMEDIATE 'ALTER TABLE SLY_FILE ADD CONSTRAINT SLY_FILE_AI_PK PRIMARY KEY (id)';
  END IF;
END;
CREATE SEQUENCE SLY_FILE_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1;
CREATE TRIGGER SLY_FILE_AI_PK
   BEFORE INSERT
   ON SLY_FILE
   FOR EACH ROW
DECLARE
   last_Sequence NUMBER;
   last_InsertID NUMBER;
BEGIN
   SELECT SLY_FILE_SEQ.NEXTVAL INTO :NEW.id FROM DUAL;
   IF (:NEW.id IS NULL OR :NEW.id = 0) THEN
      SELECT SLY_FILE_SEQ.NEXTVAL INTO :NEW.id FROM DUAL;
   ELSE
      SELECT NVL(Last_Number, 0) INTO last_Sequence
        FROM User_Sequences
       WHERE Sequence_Name = 'SLY_FILE_SEQ';
      SELECT :NEW.id INTO last_InsertID FROM DUAL;
      WHILE (last_InsertID > last_Sequence) LOOP
         SELECT SLY_FILE_SEQ.NEXTVAL INTO last_Sequence FROM DUAL;
      END LOOP;
   END IF;
END;
CREATE INDEX filename ON sly_file (filename);
CREATE TABLE sly_file_category (id NUMBER(10) NOT NULL, name VARCHAR2(255) NOT NULL, re_id NUMBER(10) NOT NULL, path VARCHAR2(255) NOT NULL, attributes TEXT NULL, createdate NUMBER(10) NOT NULL, updatedate NUMBER(10) NOT NULL, createuser VARCHAR2(255) NOT NULL, updateuser VARCHAR2(255) NOT NULL, revision NUMBER(10) DEFAULT 0 NOT NULL, PRIMARY KEY(id));
DECLARE
  constraints_Count NUMBER;
BEGIN
  SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count FROM USER_CONSTRAINTS WHERE TABLE_NAME = 'SLY_FILE_CATEGORY' AND CONSTRAINT_TYPE = 'P';
  IF constraints_Count = 0 OR constraints_Count = '' THEN
    EXECUTE IMMEDIATE 'ALTER TABLE SLY_FILE_CATEGORY ADD CONSTRAINT SLY_FILE_CATEGORY_AI_PK PRIMARY KEY (id)';
  END IF;
END;
CREATE SEQUENCE SLY_FILE_CATEGORY_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1;
CREATE TRIGGER SLY_FILE_CATEGORY_AI_PK
   BEFORE INSERT
   ON SLY_FILE_CATEGORY
   FOR EACH ROW
DECLARE
   last_Sequence NUMBER;
   last_InsertID NUMBER;
BEGIN
   SELECT SLY_FILE_CATEGORY_SEQ.NEXTVAL INTO :NEW.id FROM DUAL;
   IF (:NEW.id IS NULL OR :NEW.id = 0) THEN
      SELECT SLY_FILE_CATEGORY_SEQ.NEXTVAL INTO :NEW.id FROM DUAL;
   ELSE
      SELECT NVL(Last_Number, 0) INTO last_Sequence
        FROM User_Sequences
       WHERE Sequence_Name = 'SLY_FILE_CATEGORY_SEQ';
      SELECT :NEW.id INTO last_InsertID FROM DUAL;
      WHILE (last_InsertID > last_Sequence) LOOP
         SELECT SLY_FILE_CATEGORY_SEQ.NEXTVAL INTO last_Sequence FROM DUAL;
      END LOOP;
   END IF;
END;
CREATE TABLE sly_user (id NUMBER(10) NOT NULL, name VARCHAR(255) NULL, description VARCHAR(255) NULL, login VARCHAR2(50) NOT NULL, psw CHAR(40), status NUMBER(1) NOT NULL, rights TEXT NOT NULL, lasttrydate NUMBER(10) DEFAULT 0 NOT NULL, timezone VARCHAR(64) NULL, createdate NUMBER(10) NOT NULL, updatedate NUMBER(10) NOT NULL, createuser VARCHAR2(255) NOT NULL, updateuser VARCHAR2(255) NOT NULL, revision NUMBER(10) DEFAULT 0 NOT NULL, PRIMARY KEY(id));
DECLARE
  constraints_Count NUMBER;
BEGIN
  SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count FROM USER_CONSTRAINTS WHERE TABLE_NAME = 'SLY_USER' AND CONSTRAINT_TYPE = 'P';
  IF constraints_Count = 0 OR constraints_Count = '' THEN
    EXECUTE IMMEDIATE 'ALTER TABLE SLY_USER ADD CONSTRAINT SLY_USER_AI_PK PRIMARY KEY (id)';
  END IF;
END;
CREATE SEQUENCE SLY_USER_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1;
CREATE TRIGGER SLY_USER_AI_PK
   BEFORE INSERT
   ON SLY_USER
   FOR EACH ROW
DECLARE
   last_Sequence NUMBER;
   last_InsertID NUMBER;
BEGIN
   SELECT SLY_USER_SEQ.NEXTVAL INTO :NEW.id FROM DUAL;
   IF (:NEW.id IS NULL OR :NEW.id = 0) THEN
      SELECT SLY_USER_SEQ.NEXTVAL INTO :NEW.id FROM DUAL;
   ELSE
      SELECT NVL(Last_Number, 0) INTO last_Sequence
        FROM User_Sequences
       WHERE Sequence_Name = 'SLY_USER_SEQ';
      SELECT :NEW.id INTO last_InsertID FROM DUAL;
      WHILE (last_InsertID > last_Sequence) LOOP
         SELECT SLY_USER_SEQ.NEXTVAL INTO last_Sequence FROM DUAL;
      END LOOP;
   END IF;
END;
CREATE TABLE sly_slice (id NUMBER(10) NOT NULL, module VARCHAR2(64) NOT NULL, PRIMARY KEY(id));
DECLARE
  constraints_Count NUMBER;
BEGIN
  SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count FROM USER_CONSTRAINTS WHERE TABLE_NAME = 'SLY_SLICE' AND CONSTRAINT_TYPE = 'P';
  IF constraints_Count = 0 OR constraints_Count = '' THEN
    EXECUTE IMMEDIATE 'ALTER TABLE SLY_SLICE ADD CONSTRAINT SLY_SLICE_AI_PK PRIMARY KEY (id)';
  END IF;
END;
CREATE SEQUENCE SLY_SLICE_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1;
CREATE TRIGGER SLY_SLICE_AI_PK
   BEFORE INSERT
   ON SLY_SLICE
   FOR EACH ROW
DECLARE
   last_Sequence NUMBER;
   last_InsertID NUMBER;
BEGIN
   SELECT SLY_SLICE_SEQ.NEXTVAL INTO :NEW.id FROM DUAL;
   IF (:NEW.id IS NULL OR :NEW.id = 0) THEN
      SELECT SLY_SLICE_SEQ.NEXTVAL INTO :NEW.id FROM DUAL;
   ELSE
      SELECT NVL(Last_Number, 0) INTO last_Sequence
        FROM User_Sequences
       WHERE Sequence_Name = 'SLY_SLICE_SEQ';
      SELECT :NEW.id INTO last_InsertID FROM DUAL;
      WHILE (last_InsertID > last_Sequence) LOOP
         SELECT SLY_SLICE_SEQ.NEXTVAL INTO last_Sequence FROM DUAL;
      END LOOP;
   END IF;
END;
CREATE TABLE sly_slice_value (id NUMBER(10) NOT NULL, slice_id NUMBER(10) NOT NULL, finder VARCHAR2(50) NOT NULL, value TEXT NOT NULL, PRIMARY KEY(id));
DECLARE
  constraints_Count NUMBER;
BEGIN
  SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count FROM USER_CONSTRAINTS WHERE TABLE_NAME = 'SLY_SLICE_VALUE' AND CONSTRAINT_TYPE = 'P';
  IF constraints_Count = 0 OR constraints_Count = '' THEN
    EXECUTE IMMEDIATE 'ALTER TABLE SLY_SLICE_VALUE ADD CONSTRAINT SLY_SLICE_VALUE_AI_PK PRIMARY KEY (id)';
  END IF;
END;
CREATE SEQUENCE SLY_SLICE_VALUE_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1;
CREATE TRIGGER SLY_SLICE_VALUE_AI_PK
   BEFORE INSERT
   ON SLY_SLICE_VALUE
   FOR EACH ROW
DECLARE
   last_Sequence NUMBER;
   last_InsertID NUMBER;
BEGIN
   SELECT SLY_SLICE_VALUE_SEQ.NEXTVAL INTO :NEW.id FROM DUAL;
   IF (:NEW.id IS NULL OR :NEW.id = 0) THEN
      SELECT SLY_SLICE_VALUE_SEQ.NEXTVAL INTO :NEW.id FROM DUAL;
   ELSE
      SELECT NVL(Last_Number, 0) INTO last_Sequence
        FROM User_Sequences
       WHERE Sequence_Name = 'SLY_SLICE_VALUE_SEQ';
      SELECT :NEW.id INTO last_InsertID FROM DUAL;
      WHILE (last_InsertID > last_Sequence) LOOP
         SELECT SLY_SLICE_VALUE_SEQ.NEXTVAL INTO last_Sequence FROM DUAL;
      END LOOP;
   END IF;
END;
CREATE INDEX slice_id ON sly_slice_value (slice_id);
CREATE TABLE sly_registry (name VARCHAR2(255) NOT NULL, value BLOB NOT NULL, PRIMARY KEY(name));

-- populate database with some initial data
INSERT INTO sly_clang (name, locale) VALUES ('deutsch', 'de_DE');
