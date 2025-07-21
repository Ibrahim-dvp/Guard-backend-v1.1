# Lead Management System - Cahier de Charges

## 1. Project Overview

### 1.1 Project Objective
Development of a comprehensive lead management system that processes leads from a WordPress form and manages their distribution through a hierarchical sales organization structure.

### 1.2 Development Approach
- **Frontend**: Next.js with TypeScript
- **Phase 1**: Mock data implementation (to build complete frontend independently before backend development)
- **Phase 2**: API integration (backend to be developed after frontend completion)
- **Mock Data Rationale**: Using mock data allows complete frontend development and testing without backend dependencies, enabling faster iteration and easier API integration later

## 2. User Roles & Hierarchy

### 2.1 Role Structure (Top to Bottom)
1. **Admin** (Development Team)
2. **Protecta Group Directors**
3. **Partner Directors** (Protecta is main partner, others can join)
4. **Coordinator** (Leads Distribution Officer - Single user for Protecta)
5. **Sales Managers**
6. **Sales Agents**
7. **Referrals** (Dealers/Retail Agents)

### 2.2 Access Control Rules
- **Hierarchical Access**: Upper roles have access to everything lower roles can access
- **Organization Boundaries**: Access limited to same organization/team (except Protecta Directors and Admins)
- **Creator Relationship**: Each role has full access to users they created
- **Global Access**: Protecta Directors and Admins can access all information

## 3. Core Workflow

### 3.1 Lead Processing Flow
1. **Lead Submission**: WordPress form captures lead data
2. **Notification**: Coordinator receives notification
3. **Assignment**: Coordinator assigns lead to any Sales Manager
4. **Delegation**: Sales Manager assigns to their own Sales Agents only (or declines lead, sending it back to Coordinator)
5. **Acceptance**: Sales Agent accepts/rejects assignment (rejected leads go back to Sales Manager)
6. **Client Contact**: Upon acceptance, client and referral receive status updates

### 3.2 Notification System
- Lead submitted → Coordinator
- Lead assigned to Sales Manager → Sales Manager
- Lead assigned to Sales Agent → Sales Agent
- Lead declined by Sales Manager → Coordinator
- Lead declined by Sales Agent → Sales Manager
- Lead accepted → Client + Referral
- Status updates → Relevant parties

## 4. Functional Requirements

### 4.1 Admin Panel Features
- **User Management**: Full CRUD operations for all users
- **System Configuration**: Role permissions, notification settings
- **Data Management**: Access to all leads, reports, and analytics
- **Audit Trail**: Track all system activities

### 4.2 Protecta Group Directors
- **Partner Overview**: View all partners and their hierarchies
- **Team Management**: Create/manage partner directors
- **Comprehensive Reports**: Sales statistics, team performance, appointments
- **Full System Access**: All features available to lower roles

### 4.3 Partner Directors
- **Team Creation**: Create and manage sales teams
- **User Management**: Create Sales Managers
- **Organization Reports**: View partner-specific analytics
- **Team Hierarchy**: Visual representation of organization structure

### 4.4 Coordinator (Leads Distribution Officer)
- **Lead Queue**: Dashboard showing all incoming leads
- **Assignment Interface**: Assign leads to any Sales Manager across the system
- **Status Tracking**: Monitor lead progress through pipeline
- **Manager Overview**: View all Sales Managers and their capacity
- **Declined Leads**: Handle leads declined by Sales Managers for reassignment

### 4.5 Sales Managers
- **Team Management**: Create and manage Sales Agents
- **Lead Distribution**: Assign leads only to their own team members
- **Lead Decline**: Option to decline leads, sending them back to Coordinator
- **Declined Agent Leads**: Handle leads declined by their Sales Agents for reassignment
- **Performance Dashboard**: Team and individual performance metrics
- **Appointment Scheduling**: Manage team appointments
- **Status Monitoring**: Track lead progression for entire team

### 4.6 Sales Agents
- **Personal Dashboard**: Individual performance metrics
- **Lead Management**: View assigned leads with status updates
- **Lead Accept/Decline**: Accept or decline assigned leads (declined leads return to Sales Manager)
- **Appointment System**: Schedule and manage client meetings
- **Status Updates**: Update lead status with notes and progress
- **Filtering & Search**: Advanced filters for lead management

### 4.7 Referrals (Dealers)
- **Lead Entry**: Submit new leads through form interface
- **Lead Dashboard**: View all submitted leads with status
- **Revenue Tracking**: Monitor conversion rates and earnings
- **Status Notifications**: Receive updates on lead progress

## 5. Data Models

### 5.1 User Model
```typescript
interface User {
  id: string;
  email: string;
  firstName: string;
  lastName: string;
  role: UserRole;
  organizationId: string;
  createdBy: string;
  createdAt: Date;
  updatedAt: Date;
  isActive: boolean;
  permissions: Permission[];
}
```

### 5.2 Lead Model
```typescript
interface Lead {
  id: string;
  referralId: string;
  clientInfo: ClientInfo;
  status: LeadStatus;
  assignedTo: string;
  assignedBy: string;
  createdAt: Date;
  updatedAt: Date;
  notes: Note[];
  appointments: Appointment[];
  revenue: number;
  source: string;
}
```

### 5.3 Lead Status Enum
```typescript
enum LeadStatus {
  NEW = 'new',
  ASSIGNED_TO_MANAGER = 'assigned_to_manager',
  DECLINED_BY_MANAGER = 'declined_by_manager',
  ASSIGNED_TO_AGENT = 'assigned_to_agent',
  DECLINED_BY_AGENT = 'declined_by_agent',
  ACCEPTED = 'accepted',
  CONTACTED = 'contacted',
  QUALIFIED = 'qualified',
  CONVERTED = 'converted',
  REJECTED = 'rejected'
}
```

### 5.4 Organization Model
```typescript
interface Organization {
  id: string;
  name: string;
  parentId: string | null;
  directorId: string;
  createdAt: Date;
  isActive: boolean;
}
```

## 6. User Interface Requirements

### 6.1 Design System
- **Theme**: Professional business theme with trustworthy and modern appearance
- **Color Palette**: Corporate colors that convey professionalism and reliability
- **Typography**: Primary font - Outfit, Secondary font - Grotesc
- **Style**: Clean, modern, and intuitive interface design

### 6.2 Responsive Design
- Mobile-first approach
- Tablet and desktop optimization
- Touch-friendly interface

### 6.3 Dashboard Requirements
- **Role-based Layouts**: Different dashboard layouts per role
- **Data Visualizations**: Charts, graphs, and metrics
- **Real-time Updates**: Live status updates and notifications
- **Filtering & Search**: Advanced filtering capabilities

### 6.4 Navigation
- **Sidebar Navigation**: Collapsible sidebar with role-based menu items
- **Breadcrumb Navigation**: Clear navigation path
- **Quick Actions**: Frequently used actions easily accessible

## 7. Technical Implementation

### 7.1 Mock Data Strategy
- Create realistic mock data for each entity
- Maintain proper hierarchical relationships
- Simulate real-time data changes
- Enable easy transition to API integration

### 7.2 API Abstraction Layer
- Service layer design for seamless API transition
- Consistent data handling patterns
- Error handling and loading states
- Authentication management

This cahier de charges provides a comprehensive roadmap for developing your lead management system, ensuring a solid foundation for future API integration.