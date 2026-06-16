namespace Project.Gawad.Domain.Objects;

public class ServiceResponse<T> where T : new()
{
    public ServiceResponse()
    {
        Message = string.Empty;
        ModelState = new Dictionary<string, string>();
        Data = new T();
    }

    public ServiceResponse(T data, string message)
    {
        Message = message;
        ModelState = new Dictionary<string, string>();
        Data = data;
    }

    public ServiceResponse(T data)
    {
        Message = string.Empty;
        ModelState = new Dictionary<string, string>();
        Data = data;
    }

    public T Data { get; set; }

    public Dictionary<string, string> ModelState { get; set; }

    public string Message { get; set; }

    public bool IsSuccess => !(ModelState.Count > 0);

    public void AddModelError(string key, string errorMessage)
    {
        ModelState.Add(key, errorMessage);
    }

    // protected T GetObject<T>(Type[] signature, object[] args) => (T)typeof(T).GetConstructor(signature)?.Invoke(args);
}