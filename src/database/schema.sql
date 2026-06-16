CREATE DATABASE IF NOT EXISTS typequest
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE typequest;

CREATE TABLE IF NOT EXISTS PALAVRA (
    idPalavra    INT          NOT NULL AUTO_INCREMENT,
    texto        VARCHAR(80)  NOT NULL,
    dificuldade  INT          NOT NULL DEFAULT 1 COMMENT '1=fácil 2=médio 3=difícil 4=muito difícil 5=extremo',
    idioma       CHAR(5)      NOT NULL DEFAULT 'pt-BR',
    CONSTRAINT PK_PALAVRA PRIMARY KEY (idPalavra),
    CONSTRAINT CHK_PALAVRA_dificuldade CHECK (dificuldade BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS INIMIGO (
    idInimigo    INT          NOT NULL AUTO_INCREMENT,
    nome         VARCHAR(60)  NOT NULL,
    sprite       VARCHAR(100) NOT NULL COMMENT 'Emoji ou nome do asset',
    nivel_minimo INT          NOT NULL DEFAULT 1,
    hp           INT          NOT NULL DEFAULT 100,
    dano_base    INT          NOT NULL DEFAULT 10,
    tipo         VARCHAR(30)  NOT NULL,
    CONSTRAINT PK_INIMIGO PRIMARY KEY (idInimigo),
    CONSTRAINT CHK_INIMIGO_hp    CHECK (hp > 0),
    CONSTRAINT CHK_INIMIGO_dano  CHECK (dano_base > 0),
    CONSTRAINT CHK_INIMIGO_nivel CHECK (nivel_minimo >= 1)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS USUARIO (
    idUsuario     INT          NOT NULL AUTO_INCREMENT,
    nome_usuario  VARCHAR(50)  NOT NULL,
    email         VARCHAR(100) NOT NULL,
    senha_hash    VARCHAR(255) NOT NULL,
    data_cadastro DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_login  DATETIME         NULL,
    CONSTRAINT PK_USUARIO      PRIMARY KEY (idUsuario),
    CONSTRAINT UQ_USUARIO_email UNIQUE (email),
    CONSTRAINT UQ_USUARIO_nome  UNIQUE (nome_usuario)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS LIGA (
    idLiga               INT         NOT NULL AUTO_INCREMENT,
    FK_USUARIO_idUsuario INT         NOT NULL COMMENT 'Criador da liga',
    nome                 VARCHAR(80) NOT NULL,
    palavra_chave        VARCHAR(50) NOT NULL COMMENT 'Código para entrar na liga',
    descricao            TEXT            NULL,
    data_criacao         DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT PK_LIGA       PRIMARY KEY (idLiga),
    CONSTRAINT UQ_LIGA_nome  UNIQUE (nome),
    CONSTRAINT FK_LIGA_USUARIO
        FOREIGN KEY (FK_USUARIO_idUsuario) REFERENCES USUARIO (idUsuario)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS PARTIDA (
    idPartida            INT              NOT NULL AUTO_INCREMENT,
    FK_USUARIO_idUsuario INT              NOT NULL,
    FK_INIMIGO_idInimigo INT              NOT NULL,
    pontuacao            INT              NOT NULL DEFAULT 0,
    wpm                  INT              NOT NULL DEFAULT 0,
    precisao             DECIMAL(5,2)     NOT NULL DEFAULT 0.00,
    nivel_atingido       INT              NOT NULL DEFAULT 1,
    resultado            ENUM('vitoria','derrota') NOT NULL,
    data_partida         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    duracao_segundos     INT              NOT NULL DEFAULT 0,
    CONSTRAINT PK_PARTIDA PRIMARY KEY (idPartida),
    CONSTRAINT CHK_PARTIDA_precisao  CHECK (precisao BETWEEN 0 AND 100),
    CONSTRAINT CHK_PARTIDA_pontuacao CHECK (pontuacao >= 0),
    CONSTRAINT CHK_PARTIDA_wpm       CHECK (wpm >= 0),
    CONSTRAINT FK_PARTIDA_USUARIO
        FOREIGN KEY (FK_USUARIO_idUsuario) REFERENCES USUARIO (idUsuario)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT FK_PARTIDA_INIMIGO
        FOREIGN KEY (FK_INIMIGO_idInimigo) REFERENCES INIMIGO (idInimigo)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS PARTIDA_PALAVRA (
    FK_PARTIDA_idPartida INT       NOT NULL,
    FK_PALAVRA_idPalavra INT       NOT NULL,
    acertou              TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT PK_PARTIDA_PALAVRA
        PRIMARY KEY (FK_PARTIDA_idPartida, FK_PALAVRA_idPalavra),
    CONSTRAINT FK_PP_PARTIDA
        FOREIGN KEY (FK_PARTIDA_idPartida) REFERENCES PARTIDA (idPartida)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT FK_PP_PALAVRA
        FOREIGN KEY (FK_PALAVRA_idPalavra) REFERENCES PALAVRA (idPalavra)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS USUARIO_LIGA (
    FK_USUARIO_idUsuario INT      NOT NULL,
    FK_LIGA_idLiga       INT      NOT NULL,
    data_entrada         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pontuacao_total      INT      NOT NULL DEFAULT 0,
    pontuacao_semanal    INT      NOT NULL DEFAULT 0,
    CONSTRAINT PK_USUARIO_LIGA
        PRIMARY KEY (FK_USUARIO_idUsuario, FK_LIGA_idLiga),
    CONSTRAINT FK_UL_USUARIO
        FOREIGN KEY (FK_USUARIO_idUsuario) REFERENCES USUARIO (idUsuario)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT FK_UL_LIGA
        FOREIGN KEY (FK_LIGA_idLiga) REFERENCES LIGA (idLiga)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS PONTUACAO_SEMANAL (
    idPontuacao          INT  NOT NULL AUTO_INCREMENT,
    FK_USUARIO_idUsuario INT  NOT NULL,
    FK_LIGA_idLiga       INT      NULL COMMENT 'NULL = ranking global',
    pontuacao            INT  NOT NULL DEFAULT 0,
    semana_inicio        DATE NOT NULL,
    semana_fim           DATE NOT NULL,
    CONSTRAINT PK_PONTUACAO_SEMANAL PRIMARY KEY (idPontuacao),
    CONSTRAINT UQ_PS_usuario_liga_semana
        UNIQUE (FK_USUARIO_idUsuario, FK_LIGA_idLiga, semana_inicio),
    CONSTRAINT CHK_PS_datas    CHECK (semana_fim >= semana_inicio),
    CONSTRAINT CHK_PS_pontuacao CHECK (pontuacao >= 0),
    CONSTRAINT FK_PS_USUARIO
        FOREIGN KEY (FK_USUARIO_idUsuario) REFERENCES USUARIO (idUsuario)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT FK_PS_LIGA
        FOREIGN KEY (FK_LIGA_idLiga) REFERENCES LIGA (idLiga)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
