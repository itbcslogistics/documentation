# Implementation Plan: Leave Quota System

## Goal
Implement a leave quota tracking system where:
- Each user has **12 days** of annual leave quota
- Quota is deducted when leave request is **approved**
- Users can check their remaining quota via API
- System validates that user has sufficient quota before approval

## Proposed Changes

### 1. Database Migration

#### [NEW] Add leave_quota column to users table
```php
Schema::table('users', function (Blueprint $table) {
    $table->integer('annual_leave_quota')->default(12);
    $table->integer('used_leave_quota')->default(0);
});
```

### 2. Model Updates

#### [MODIFY] `app/Models/User.php`
- Add `annual_leave_quota` and `used_leave_quota` to fillable
- Add helper method `getRemainingLeaveQuota()`
- Add helper method `hasLeaveQuota($days)`

### 3. API Endpoints

#### [NEW] `GET /api/leave-quota`
Returns user's leave quota information:
```json
{
  "annual_quota": 12,
  "used_quota": 3,
  "remaining_quota": 9
}
```

### 4. Leave Request Logic

#### [MODIFY] `app/Http/Controllers/AdminActionController.php`
- Update `approvePermission()` method
- Calculate leave days from `start_date` to `end_date`
- Check if user has sufficient quota
- Deduct quota when approving leave (type: 'leave' or 'annual_leave')
- Return error if insufficient quota

#### [MODIFY] `app/Models/PermissionRequest.php`
- Add method `calculateLeaveDays()` to count working days
- Add method `isLeaveType()` to check if request is for leave

### 5. Validation

- Validate quota before approval
- Prevent approval if insufficient quota
- Show remaining quota in error message

## Verification Plan

### Manual Testing via Postman

**Test 1: Check Leave Quota**
```
GET http://127.0.0.1:8000/api/leave-quota
Headers: Authorization: Bearer {token}

Expected: Returns quota information with 12 annual, 0 used, 12 remaining
```

**Test 2: Submit Leave Request**
```
POST http://127.0.0.1:8000/api/permissions
Headers: Authorization: Bearer {token}
Body: {
  "type": "leave",
  "start_date": "2026-01-20",
  "end_date": "2026-01-22",
  "reason": "Family vacation"
}

Expected: Request created successfully
```

**Test 3: Approve Leave (Admin)**
```
POST http://127.0.0.1:8000/admin-api/permissions/{id}/approve

Expected: 
- Request approved
- User's used_quota increased by 3
- Remaining quota decreased to 9
```

**Test 4: Check Quota After Approval**
```
GET http://127.0.0.1:8000/api/leave-quota

Expected: Returns 12 annual, 3 used, 9 remaining
```

**Test 5: Insufficient Quota**
```
1. Submit leave request for 10 days
2. Approve it (remaining: 2 days)
3. Submit another leave request for 5 days
4. Try to approve it

Expected: Error message "Insufficient leave quota. Remaining: 2 days, Required: 5 days"
```

### Database Verification
```sql
SELECT id, name, annual_leave_quota, used_leave_quota 
FROM users 
WHERE id = 1;
```

Should show quota changes after approvals.

## Notes

- Quota resets annually (can be implemented later with cron job)
- Only 'leave' or 'annual_leave' type requests deduct quota
- Sick leave, permission, etc. do not affect quota
- Rejected requests do not deduct quota
- If approved request is later rejected, quota should be restored (future enhancement)
