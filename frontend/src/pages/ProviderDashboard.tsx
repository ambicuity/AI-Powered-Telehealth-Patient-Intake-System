import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Calendar, Users, FileText, LogOut, Heart, Activity } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useAuthStore } from '@/store/auth';
import { apiClient } from '@/lib/api';

interface ProviderStats {
  total_appointments: number;
  todays_appointments: number;
  pending_appointments: number;
  patients_count: number;
}

export default function ProviderDashboard() {
  const { user, logout } = useAuthStore();
  const [activeTab, setActiveTab] = useState<'overview' | 'appointments' | 'patients'>('overview');

  const { data: stats } = useQuery<ProviderStats>({
    queryKey: ['provider-stats'],
    queryFn: () => apiClient.get('/providers/me/stats'),
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
              <h1 className="text-2xl font-bold text-gray-900">Provider Portal</h1>
            </div>
            <div className="flex items-center space-x-4">
              <span className="text-sm text-gray-700">Dr. {user?.name}</span>
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
                { id: 'overview', name: 'Overview', icon: Activity },
                { id: 'appointments', name: 'Appointments', icon: Calendar },
                { id: 'patients', name: 'Patients', icon: Users },
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
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-6">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Today's Appointments</CardTitle>
                <Calendar className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats?.todays_appointments || 0}</div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total Appointments</CardTitle>
                <Activity className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats?.total_appointments || 0}</div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Pending Appointments</CardTitle>
                <Calendar className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats?.pending_appointments || 0}</div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total Patients</CardTitle>
                <Users className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats?.patients_count || 0}</div>
              </CardContent>
            </Card>
          </div>

          {/* Tab Content */}
          {activeTab === 'overview' && (
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <Card>
                <CardHeader>
                  <CardTitle>Today's Schedule</CardTitle>
                  <CardDescription>Your appointments for today</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="text-center py-6">
                    <Calendar className="mx-auto h-12 w-12 text-gray-400" />
                    <h3 className="mt-2 text-sm font-medium text-gray-900">No appointments today</h3>
                    <p className="mt-1 text-sm text-gray-500">
                      Your schedule is clear for today
                    </p>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Recent Patient Intake</CardTitle>
                  <CardDescription>Latest intake forms processed</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="text-center py-6">
                    <FileText className="mx-auto h-12 w-12 text-gray-400" />
                    <h3 className="mt-2 text-sm font-medium text-gray-900">No recent intake forms</h3>
                    <p className="mt-1 text-sm text-gray-500">
                      No new intake forms to review
                    </p>
                  </div>
                </CardContent>
              </Card>
            </div>
          )}

          {activeTab === 'appointments' && (
            <Card>
              <CardHeader>
                <CardTitle>All Appointments</CardTitle>
                <CardDescription>Manage your patient appointments</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-center py-6">
                  <Calendar className="mx-auto h-12 w-12 text-gray-400" />
                  <h3 className="mt-2 text-sm font-medium text-gray-900">No appointments</h3>
                  <p className="mt-1 text-sm text-gray-500">
                    No appointments scheduled at this time
                  </p>
                </div>
              </CardContent>
            </Card>
          )}

          {activeTab === 'patients' && (
            <Card>
              <CardHeader>
                <CardTitle>Patient List</CardTitle>
                <CardDescription>View and manage your patients</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-center py-6">
                  <Users className="mx-auto h-12 w-12 text-gray-400" />
                  <h3 className="mt-2 text-sm font-medium text-gray-900">No patients</h3>
                  <p className="mt-1 text-sm text-gray-500">
                    No patients assigned to you yet
                  </p>
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      </div>
    </div>
  );
}