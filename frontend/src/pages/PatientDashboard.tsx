import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Upload, User, Calendar, FileText, LogOut, Heart } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useAuthStore } from '@/store/auth';
import { apiClient } from '@/lib/api';

interface DashboardStats {
  appointments_count: number;
  intake_forms_count: number;
  pending_appointments: number;
}

export default function PatientDashboard() {
  const { user, logout } = useAuthStore();
  const [activeTab, setActiveTab] = useState<'profile' | 'intake' | 'appointments'>('profile');

  const { data: stats } = useQuery<DashboardStats>({
    queryKey: ['dashboard-stats'],
    queryFn: () => apiClient.get('/patients/me/stats'),
  });

  const handleLogout = () => {
    logout();
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-6">
            <div className="flex items-center">
              <Heart className="h-8 w-8 text-primary mr-3" />
              <h1 className="text-2xl font-bold text-gray-900">Telehealth Portal</h1>
            </div>
            <div className="flex items-center space-x-4">
              <span className="text-sm text-gray-700">Welcome, {user?.name}</span>
              <Button variant="outline" size="sm" onClick={handleLogout}>
                <LogOut className="h-4 w-4 mr-2" />
                Logout
              </Button>
            </div>
          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div className="px-4 py-6 sm:px-0">
          {/* Navigation Tabs */}
          <div className="border-b border-gray-200 mb-6">
            <nav className="-mb-px flex space-x-8">
              {[
                { id: 'profile', name: 'Profile', icon: User },
                { id: 'intake', name: 'Intake Forms', icon: FileText },
                { id: 'appointments', name: 'Appointments', icon: Calendar },
              ].map(({ id, name, icon: Icon }) => (
                <button
                  key={id}
                  onClick={() => setActiveTab(id as any)}
                  className={`${
                    activeTab === id
                      ? 'border-primary text-primary'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center`}
                >
                  <Icon className="h-4 w-4 mr-2" />
                  {name}
                </button>
              ))}
            </nav>
          </div>

          {/* Stats Cards */}
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total Appointments</CardTitle>
                <Calendar className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats?.appointments_count || 0}</div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Intake Forms</CardTitle>
                <FileText className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats?.intake_forms_count || 0}</div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Pending Appointments</CardTitle>
                <Upload className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats?.pending_appointments || 0}</div>
              </CardContent>
            </Card>
          </div>

          {/* Tab Content */}
          {activeTab === 'profile' && (
            <Card>
              <CardHeader>
                <CardTitle>Patient Profile</CardTitle>
                <CardDescription>Manage your personal information and insurance details</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="text-sm font-medium text-gray-700">Name</label>
                      <p className="mt-1 text-sm text-gray-900">{user?.name}</p>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-gray-700">Email</label>
                      <p className="mt-1 text-sm text-gray-900">{user?.email}</p>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-gray-700">Phone</label>
                      <p className="mt-1 text-sm text-gray-900">{user?.phone || 'Not provided'}</p>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-gray-700">Role</label>
                      <p className="mt-1 text-sm text-gray-900 capitalize">{user?.role}</p>
                    </div>
                  </div>
                  <Button className="mt-4">Edit Profile</Button>
                </div>
              </CardContent>
            </Card>
          )}

          {activeTab === 'intake' && (
            <Card>
              <CardHeader>
                <CardTitle>Intake Forms</CardTitle>
                <CardDescription>Upload and manage your medical intake forms</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                  <Upload className="mx-auto h-12 w-12 text-gray-400" />
                  <h3 className="mt-2 text-sm font-medium text-gray-900">No intake forms</h3>
                  <p className="mt-1 text-sm text-gray-500">
                    Upload your medical intake forms to get started
                  </p>
                  <Button className="mt-4">Upload Intake Form</Button>
                </div>
              </CardContent>
            </Card>
          )}

          {activeTab === 'appointments' && (
            <Card>
              <CardHeader>
                <CardTitle>Appointments</CardTitle>
                <CardDescription>View and manage your upcoming appointments</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-center py-6">
                  <Calendar className="mx-auto h-12 w-12 text-gray-400" />
                  <h3 className="mt-2 text-sm font-medium text-gray-900">No appointments</h3>
                  <p className="mt-1 text-sm text-gray-500">
                    Schedule your first appointment with a healthcare provider
                  </p>
                  <Button className="mt-4">Schedule Appointment</Button>
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      </div>
    </div>
  );
}