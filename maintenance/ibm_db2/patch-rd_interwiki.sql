CREATE TABLE redirect (
  rd_from       BIGINT  NOT NULL  PRIMARY KEY GENERATED BY DEFAULT AS IDENTITY (START WITH 1),
  --REFERENCES page(page_id) ON DELETE CASCADE,
  rd_namespace  SMALLINT NOT NULL  DEFAULT 0,
  rd_title      VARCHAR(255)     NOT NULL DEFAULT '',
  rd_interwiki  varchar(32),
  rd_fragment   VARCHAR(255)
);
