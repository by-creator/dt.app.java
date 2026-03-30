CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT,
    user_name VARCHAR(255),
    user_email VARCHAR(255),
    user_role VARCHAR(100),
    method VARCHAR(10) NOT NULL,
    url VARCHAR(1000) NOT NULL,
    route_name VARCHAR(255),
    controller_action VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload JSON,
    query_params JSON,
    session_id VARCHAR(255),
    response_status SMALLINT CHECK (response_status >= 0),
    duration_ms INT CHECK (duration_ms >= 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_audit_logs_created_at ON audit_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_method ON audit_logs(method);
CREATE INDEX IF NOT EXISTS idx_audit_logs_response_status ON audit_logs(response_status);
