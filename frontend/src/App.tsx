import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuthStore } from '@/store/auth';
import { Toaster } from '@/components/ui/toaster';

// Pages
import LoginPage from '@/pages/LoginPage';
import RegisterPage from '@/pages/RegisterPage';
import PatientDashboard from '@/pages/PatientDashboard';
import ProviderDashboard from '@/pages/ProviderDashboard';

// Protected Route Component
function ProtectedRoute({ children, allowedRoles }: { children: React.ReactNode; allowedRoles?: string[] }) {
  const { isAuthenticated, user } = useAuthStore();

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  if (allowedRoles && user && !allowedRoles.includes(user.role)) {
    return <Navigate to="/unauthorized" replace />;
  }

  return <>{children}</>;
}

// Public Route Component (redirect if authenticated)
function PublicRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, user } = useAuthStore();

  if (isAuthenticated && user) {
    const redirectPath = user.role === 'provider' ? '/provider' : '/dashboard';
    return <Navigate to={redirectPath} replace />;
  }

  return <>{children}</>;
}

function App() {
  return (
    <div className="min-h-screen bg-background">
      <Routes>
        {/* Public routes */}
        <Route
          path="/login"
          element={
            <PublicRoute>
              <LoginPage />
            </PublicRoute>
          }
        />
        <Route
          path="/register"
          element={
            <PublicRoute>
              <RegisterPage />
            </PublicRoute>
          }
        />

        {/* Protected routes */}
        <Route
          path="/dashboard"
          element={
            <ProtectedRoute allowedRoles={['patient']}>
              <PatientDashboard />
            </ProtectedRoute>
          }
        />
        <Route
          path="/provider"
          element={
            <ProtectedRoute allowedRoles={['provider']}>
              <ProviderDashboard />
            </ProtectedRoute>
          }
        />

        {/* Default redirect */}
        <Route path="/" element={<Navigate to="/dashboard" replace />} />
        
        {/* 404 and unauthorized */}
        <Route
          path="/unauthorized"
          element={
            <div className="flex items-center justify-center min-h-screen">
              <div className="text-center">
                <h1 className="text-2xl font-bold text-destructive">Unauthorized</h1>
                <p className="text-muted-foreground">You don't have permission to access this page.</p>
              </div>
            </div>
          }
        />
        <Route
          path="*"
          element={
            <div className="flex items-center justify-center min-h-screen">
              <div className="text-center">
                <h1 className="text-2xl font-bold">404 - Page Not Found</h1>
                <p className="text-muted-foreground">The page you're looking for doesn't exist.</p>
              </div>
            </div>
          }
        />
      </Routes>
      <Toaster />
    </div>
  );
}

export default App;