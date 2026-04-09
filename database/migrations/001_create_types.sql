-- Migration 001: PostgreSQL ENUM type definitions
-- Run before any table creation.

DO $$ BEGIN
    CREATE TYPE user_role AS ENUM ('admin', 'manager', 'assessor', 'viewer');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE matrix_axis AS ENUM ('severity', 'likelihood');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE assessment_status AS ENUM ('draft', 'in_review', 'approved', 'archived');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE share_permission AS ENUM ('view', 'edit');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE assessment_template AS ENUM ('simple', 'simple_natural', 'detailed', 'detailed_natural');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE control_phase AS ENUM ('existing', 'proposed');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;
