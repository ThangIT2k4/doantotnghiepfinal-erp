<?php

/**
 * Role Capabilities Configuration
 * 
 * Định nghĩa capabilities mặc định cho từng role.
 * Manager có wildcard '*' để có tất cả quyền.
 * Agent có các capabilities cụ thể theo module ERP.
 * 
 * Các capabilities được map theo cấu trúc ERP modules:
 * - party.*: Party Management
 * - crm.*: CRM Management
 * - asset.*: Asset & Inventory Management
 * - contract.*: Contract Management
 * - billing.*: AR & Billing Management
 * - work.*: Work Management
 * - finance.*: Finance Management
 */

return [
    'manager' => [
        // Manager có tất cả quyền
        '*' => true,
    ],
    'agent' => [
        // Legacy capabilities (backward compatibility)
        'property.view' => true,
        'unit.view' => true,
        'unit.create' => true,
        'unit.update' => true,
        'viewing.view' => true,
        'viewing.create' => true,
        'viewing.update' => true,
        'lead.view' => true,
        'lead.create' => true,
        'lead.update' => true,
        'ticket.view' => true,
        'ticket.create' => true,
        'ticket.update' => true,
        'invoice.view' => true,
        'invoice.create_draft' => true,
        'payment.view' => true,
        'report.view_basic' => true,
        'settings.view_self' => true,
        'settings.update_self' => true,
        'settings.organization.update' => false, // Only manager can update organization name
        'user_banking.view_self' => true,
        'user_banking.update_self' => true,

        // ERP Module Capabilities
        
        // Party Module
        'party.access' => true,
        'party.person.view' => true,
        'party.organization.view' => true,
        'party.role.view' => true,
        'party.user.view' => true,
        'party.user.view_own' => true, // Agent chỉ xem Users có role người thuê mà mình đã tạo

        // CRM Module
        'crm.access' => true,
        'crm.lead.view' => false, // Agent không xem tất cả, chỉ xem của mình
        'crm.lead.view_own' => true, // Agent chỉ xem Leads của mình
        'crm.lead.create' => true,
        'crm.lead.update' => true,
        'crm.contact.view' => true,
        'crm.appointment.view' => false, // Agent không xem tất cả, chỉ xem của mình
        'crm.appointment.view_own' => true, // Agent chỉ xem Appointments của mình
        'crm.appointment.create' => true,
        'crm.appointment.update' => true,
        'crm.pipeline.view' => true,
        'crm.review.view' => false, // Agent không xem tất cả, chỉ xem của mình
        'crm.review.view_own' => true, // Agent chỉ xem Reviews của leases mình quản lý

        // Asset Module
        'asset.access' => true,
        'asset.property.view' => false, // Agent không xem tất cả, chỉ xem được gán
        'asset.property.view_own' => true, // Agent chỉ xem Properties được gán
        'asset.unit.view' => false, // Agent không xem tất cả, chỉ xem của Properties được gán
        'asset.unit.view_own' => true, // Agent chỉ xem Units của Properties được gán
        'asset.unit.create' => true,
        'asset.unit.update' => true,
        'asset.amenity.view' => true,
        'asset.status.manage' => true,
        'asset.meter.view' => false, // Agent không xem tất cả, chỉ xem của Properties được gán
        'asset.meter.view_own' => true, // Agent can only view meters of assigned properties
        'asset.meter.create' => true, // Agent can create meters for assigned properties
        'asset.meter.update' => true, // Agent can update meters of assigned properties
        'asset.meter.delete' => true, // Agent can delete meters of assigned properties
        'asset.meter_reading.view' => false, // Agent không xem tất cả, chỉ xem của Properties được gán
        'asset.meter_reading.view_own' => true, // Agent can only view readings of meters of assigned properties
        'asset.meter_reading.create' => true, // Agent can create readings for meters of assigned properties
        'asset.meter_reading.update' => true, // Agent can update readings of meters of assigned properties
        'asset.meter_reading.delete' => true, // Agent can delete readings of meters of assigned properties
        'asset.property_type.view' => true, // Agent can only view property types (for selection in forms)

        // Contract Module
        'contract.access' => true,
        'contract.lease.view' => false, // Agent không xem tất cả, chỉ xem của mình
        'contract.lease.view_own' => true, // Agent chỉ xem Leases của mình
        'contract.lease.create' => true,
        'contract.lease.update' => true,
        'contract.resident.view' => true,
        'contract.resident.manage' => true,
        'contract.deposit_refund.view' => false, // Agent không xem tất cả, chỉ xem của mình
        'contract.deposit_refund.view_own' => true, // Agent chỉ xem Deposit Refunds của mình
        'contract.deposit_refund.create' => true,
        'contract.deposit_refund.update' => true,
        'contract.deposit_refund.delete' => true,
        'contract.booking_deposit.view' => false, // Agent không xem tất cả, chỉ xem của mình
        'contract.booking_deposit.view_own' => true, // Agent chỉ xem Booking Deposits của mình
        'contract.booking_deposit.create' => true,
        'contract.booking_deposit.update' => true,
        'contract.booking_deposit.delete' => true,
        
        // Salary Contract (limited access)
        'party.salary_contract.view' => false, // Agent không xem tất cả, chỉ xem của mình
        'party.salary_contract.view_own' => true, // Agent can only view their own salary contracts

        // Billing Module
        'billing.access' => true,
        'billing.invoice.view' => false, // Agent không xem tất cả, chỉ xem của mình
        'billing.invoice.view_own' => true, // Agent chỉ xem Invoices của mình
        'billing.invoice.create' => true,
        'billing.payment.view' => false, // Agent không xem tất cả, chỉ xem của mình
        'billing.payment.view_own' => true, // Agent chỉ xem Payments của mình
        'billing.payment.create' => true,

        // Work Management Module
        'work.access' => true,
        'work.ticket.view' => false, // Agent không xem tất cả, chỉ xem của mình
        'work.ticket.view_own' => true, // Agent chỉ xem Tickets của mình
        'work.ticket.create' => true,
        'work.ticket.update' => true,
        'work.log.view' => true,
        'work.log.create' => true,

        // Finance Module (limited access)
        'finance.cashflow.view' => true,
        'finance.commission.view' => false, // Agent không xem tất cả, chỉ xem của mình
        'finance.commission.view_own' => true, // Agent can only view their own commission events
        'finance.payroll.view' => false, // Agent không xem tất cả, chỉ xem của mình
        'finance.payroll.view_own' => true, // Agent can only view their own payslips
        'finance.salary_advance.view' => false, // Agent không xem tất cả, chỉ xem của mình
        'finance.salary_advance.view_own' => true, // Agent can only view their own salary advances
        'finance.salary_advance.create' => true, // Agent can create salary advances for themselves
        'finance.salary_advance.update' => true, // Agent can update their own salary advances
        'finance.salary_advance.delete' => true, // Agent can delete their own salary advances
        'finance.report.view' => true,
        'finance.report.export' => true, // Agent can export reports
    ],
];


